"""
BIOM9450 Major Project: patient-level cancer type prediction (TCGA PanCancer Atlas, via cBioPortal).
written by Christian Schmooel (z5417031)

Usage:  (run from biom_project/):  python train_tcga.py
Data:   tcga_data/<study>/data_mutations.txt
Outputs: model_output_tcga_v2/  (v1 results in model_output_tcga/ are kept unchanged for changelog history)

V2 fixes compared with V1:
  1. Variant-classification vocabulary is a FIXED list from the MAF specification
     (v1 learned it from all patients, including the held-out test set).
  2. Top-gene selection is a pipeline step (TopGeneSelector), so it is refitted on each CV training fold
     and then on all outer-training patients (v1 selected genes once, before GridSearchCV).
  3. Duplicate mutations are removed by genomic identity (patient, chromosome, start, end, ref, alt)
     (v1 ignored position, so distinct mutations in the same gene could collapse).

The website re-implements prepare()/dense_features()/gene indicators in assets/js/predict.js;
parity_fixtures.json lets us test that JS produces the same features and scores as Python.
"""
import json, os, shutil, time, warnings
import numpy as np, pandas as pd
import scipy.sparse as sp
import matplotlib; matplotlib.use("Agg")
import matplotlib.pyplot as plt
from sklearn.base import BaseEstimator, TransformerMixin
from sklearn.model_selection import train_test_split, StratifiedKFold, GridSearchCV, cross_val_score
from sklearn.dummy import DummyClassifier
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.svm import SVC
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (accuracy_score, balanced_accuracy_score, f1_score, precision_score, recall_score,
                             roc_auc_score, confusion_matrix, ConfusionMatrixDisplay, classification_report)
warnings.filterwarnings("ignore")

SEED, TOP_GENES, TEST_FRAC, DATA, OUT = 42, 300, 0.20, "tcga_data", "model_output_tcga_v2"
MODEL_VERSION = "tcga-rf-compact-v2"
WEB_MODEL_DIR = os.path.join("..", "OncoAtlas_BIOM9450_Website", "assets", "model")
STUDIES = {"laml": "Blood", "lgg": "Brain", "brca": "Breast", "lihc": "Liver", "paad": "Pancreas", "prad": "Prostate"}
COLS = ["Hugo_Symbol", "NCBI_Build", "Chromosome", "Start_Position", "End_Position", "Reference_Allele",
        "Tumor_Seq_Allele2", "Variant_Classification", "Variant_Type", "Tumor_Sample_Barcode", "IMPACT", "HGVSp_Short"]
REQUIRED = ["Hugo_Symbol", "Chromosome", "Start_Position", "End_Position", "Reference_Allele", "Tumor_Seq_Allele2",
            "Variant_Classification", "Variant_Type", "Tumor_Sample_Barcode"]
DEDUP_KEY = ["patient", "Chromosome", "Start_Position", "End_Position", "Reference_Allele", "Tumor_Seq_Allele2"]
SUBS = ["C>A", "C>G", "C>T", "T>A", "T>C", "T>G", "indel", "other"]
# Fixed vocabulary = MAF specification categories (not learned from the data); anything else -> other_vc
VCS = ["Missense_Mutation", "Nonsense_Mutation", "Silent", "Frame_Shift_Del", "Frame_Shift_Ins", "In_Frame_Del",
       "In_Frame_Ins", "Splice_Site", "Splice_Region", "Translation_Start_Site", "Nonstop_Mutation", "3'UTR",
       "3'Flank", "5'UTR", "5'Flank", "IGR", "Intron", "RNA", "Targeted_Region", "De_novo_Start_InFrame",
       "De_novo_Start_OutOfFrame", "other_vc"]
COMP = {"A": "T", "T": "A", "C": "G", "G": "C"}
os.makedirs(f"{OUT}/examples", exist_ok=True)
t0 = time.time()
rng = np.random.default_rng(SEED)


# Shared preprocessing (mirrored in OncoAtlas assets/js/predict.js) 
def prepare(df):
    """One row per distinct genomic mutation per patient, labelled with substitution class and variant class."""
    df = df.copy()
    df["patient"] = df.Tumor_Sample_Barcode.str[:12]                       # TCGA-XX-YYYY = one patient
    df = df.drop_duplicates(DEDUP_KEY)
    ref, alt = df.Reference_Allele, df.Tumor_Seq_Allele2
    snp = (df.Variant_Type == "SNP") & ref.isin(list(COMP)) & alt.isin(list(COMP))
    flip = snp & ref.isin(["A", "G"])                                      # express every SNP on the C/T strand
    r2, a2 = ref.where(~flip, ref.map(COMP)), alt.where(~flip, alt.map(COMP))
    df["sub"] = np.where(snp, r2 + ">" + a2, np.where(df.Variant_Type.isin(["INS", "DEL"]), "indel", "other"))
    df["vc"] = df.Variant_Classification.where(df.Variant_Classification.isin(VCS[:-1]), "other_vc")
    return df


def dense_features(df, index):
    """log(1 + mutation count), substitution proportions, variant-class proportions."""
    n = df.groupby("patient").size().reindex(index, fill_value=0)
    out = pd.DataFrame({"log_n_mut": np.log1p(n.values)}, index=index)
    for col, cats, pre in [("sub", SUBS, "sub_"), ("vc", VCS, "vc_")]:
        ct = pd.crosstab(df.patient, df[col], normalize="index").reindex(index=index, columns=cats, fill_value=0)
        ct.columns = [pre + c for c in cats]
        out = out.join(ct)
    return out


def gene_matrix(df, index, genes):
    """Sparse 0/1 matrix: patient has >=1 mutation in gene."""
    g = df[df.Hugo_Symbol.isin(genes) & df.patient.isin(index)].drop_duplicates(["patient", "Hugo_Symbol"])
    rows, cols = pd.Index(index).get_indexer(g.patient), pd.Index(genes).get_indexer(g.Hugo_Symbol)
    return sp.csr_matrix((np.ones(len(g), np.float32), (rows, cols)), shape=(len(index), len(genes)))


class TopGeneSelector(BaseEstimator, TransformerMixin):
    """Keep the dense features plus the k genes mutated in the most patients of the data it is FITTED on.
    Inside GridSearchCV it is refitted on every training fold, so validation folds never influence gene choice.
    Ties are broken alphabetically (candidate genes are sorted), making the selection deterministic."""
    def __init__(self, n_dense, k=300):
        self.n_dense, self.k = n_dense, k

    def fit(self, X, y=None):
        counts = np.asarray(X[:, self.n_dense:].sum(axis=0)).ravel()
        self.keep_ = np.argsort(-counts, kind="stable")[:self.k]
        return self

    def transform(self, X):
        X = sp.csr_matrix(X)
        return sp.hstack([X[:, :self.n_dense], X[:, self.n_dense + self.keep_]]).toarray().astype(np.float32)


# 1. LOAD 
parts = []
for code, name in STUDIES.items():
    d = pd.read_csv(f"{DATA}/{code}/data_mutations.txt", sep="\t", comment="#", usecols=COLS, dtype=str, low_memory=False)
    d["cancer"] = name
    parts.append(d)
    print(f"{code}: {len(d)} mutations, {d.Tumor_Sample_Barcode.str[:12].nunique()} patients")
raw = pd.concat(parts, ignore_index=True)
raw["patient"] = raw.Tumor_Sample_Barcode.str[:12]
raw = raw[~raw.patient.isin(raw.groupby("patient").cancer.nunique().loc[lambda s: s > 1].index)]  # drop patients in 2 classes
m = prepare(raw)
print(f"\nrows: {len(raw)} raw -> {len(m)} after genomic de-duplication ({len(raw) - len(m)} duplicates removed)")

# 2. PATIENT TABLE + SPLIT BY PATIENT (before anything is learned from the data) 
pat = m.groupby("patient").agg(cancer=("cancer", "first"))
print("patients per class:\n", pat.cancer.value_counts().to_string())
tr_ids, te_ids = train_test_split(pat.index, test_size=TEST_FRAC, stratify=pat.cancer, random_state=SEED)
pd.DataFrame({"patient": list(tr_ids) + list(te_ids), "set": ["train"] * len(tr_ids) + ["test"] * len(te_ids)}).to_csv(f"{OUT}/patient_split.csv", index=False)

# 3. FEATURES: dense features + candidate gene indicators (genes seen in TRAIN patients; top-k chosen in the pipeline)
dense = dense_features(m, pat.index)
N_DENSE = dense.shape[1]
CAND_GENES = sorted(m.loc[m.patient.isin(tr_ids), "Hugo_Symbol"].dropna().unique())
X = sp.hstack([sp.csr_matrix(dense.values), gene_matrix(m, pat.index, CAND_GENES)]).tocsr()
itr, ite = pat.index.get_indexer(tr_ids), pat.index.get_indexer(te_ids)
Xtr, Xte, ytr, yte = X[itr], X[ite], pat.cancer.values[itr], pat.cancer.values[ite]
classes = sorted(pat.cancer.unique())
print(f"\nfeatures per patient: {N_DENSE} dense + top {TOP_GENES} genes (from {len(CAND_GENES)} candidates)"
      f" | train patients: {len(tr_ids)} | test patients: {len(te_ids)}")


def pipe(clf, scale=False):
    steps = [("genes", TopGeneSelector(N_DENSE, TOP_GENES))] + ([("scale", StandardScaler())] if scale else [])
    return Pipeline(steps + [("clf", clf)])


def feature_names(sel):
    return list(dense.columns) + ["gene_" + CAND_GENES[i] for i in sel.keep_]


# 4. MODELS + TUNING (stratified 5-fold CV on TRAIN only, scored by macro-F1) 
cv = StratifiedKFold(5, shuffle=True, random_state=SEED)
cands = {
    "Baseline (majority)": (pipe(DummyClassifier(strategy="most_frequent")), {}),
    "Decision Tree": (pipe(DecisionTreeClassifier(class_weight="balanced", random_state=SEED)),
                      {"clf__max_depth": [4, 8, None], "clf__min_samples_leaf": [1, 5]}),
    "Random Forest": (pipe(RandomForestClassifier(class_weight="balanced", random_state=SEED, n_jobs=-1)),
                      {"clf__n_estimators": [200, 400], "clf__max_depth": [10, None], "clf__min_samples_leaf": [1, 3]}),
    "SVM (RBF)": (pipe(SVC(class_weight="balanced", probability=True, random_state=SEED), scale=True),
                  {"clf__C": [1, 10], "clf__gamma": ["scale", 0.001]}),
}
rows, fitted = [], {}
for name, (est, grid) in cands.items():
    if grid:
        gs = GridSearchCV(est, grid, cv=cv, scoring="f1_macro", n_jobs=-1).fit(Xtr, ytr)
        best, cvf1, params = gs.best_estimator_, gs.best_score_, gs.best_params_
    else:
        cvf1, params = cross_val_score(est, Xtr, ytr, cv=cv, scoring="f1_macro").mean(), {}
        best = est.fit(Xtr, ytr)
    p = best.predict(Xte)
    auc = roc_auc_score(yte, best.predict_proba(Xte), multi_class="ovr", average="macro", labels=best.classes_)
    rows.append(dict(model=name, cv_macro_f1=round(cvf1, 4), test_accuracy=round(accuracy_score(yte, p), 4),
                     test_balanced_acc=round(balanced_accuracy_score(yte, p), 4),
                     test_macro_precision=round(precision_score(yte, p, average="macro", zero_division=0), 4),
                     test_macro_recall=round(recall_score(yte, p, average="macro", zero_division=0), 4),
                     test_macro_f1=round(f1_score(yte, p, average="macro"), 4), test_auc_ovr=round(auc, 4), best_params=str(params)))
    fitted[name] = best
    print(f"{name}: CV macro-F1={cvf1:.3f} | test acc={rows[-1]['test_accuracy']:.3f} macro-F1={rows[-1]['test_macro_f1']:.3f} ({time.time()-t0:.0f}s)", flush=True)
res = pd.DataFrame(rows); res.to_csv(f"{OUT}/model_comparison.csv", index=False)

# 5. BEST MODEL (chosen by CV, not by test score): report, confusion matrices, bootstrap CI 
best_name = res.iloc[1:].sort_values("cv_macro_f1", ascending=False).iloc[0].model
best = fitted[best_name]; pred = best.predict(Xte)
open(f"{OUT}/classification_report.txt", "w").write(f"Best model by CV macro-F1: {best_name}\n\n" + classification_report(yte, pred, digits=3))
for norm, fn in [(None, "confusion_matrix.png"), ("true", "confusion_matrix_normalised.png")]:
    fig, ax = plt.subplots(figsize=(6.5, 5.5))
    ConfusionMatrixDisplay(confusion_matrix(yte, pred, labels=classes, normalize=norm), display_labels=classes).plot(
        ax=ax, cmap="Blues", xticks_rotation=45, colorbar=False, values_format=".2f" if norm else "d")
    ax.set_title(f"Test confusion matrix - {best_name}"); fig.tight_layout(); fig.savefig(f"{OUT}/{fn}", dpi=200); plt.close(fig)
boots = []
for _ in range(1000):
    i = rng.integers(0, len(yte), len(yte)); boots.append(f1_score(yte[i], pred[i], average="macro"))
lo, hi = np.percentile(boots, [2.5, 97.5])
print(f"Best = {best_name}; test macro-F1 95% bootstrap CI: [{lo:.3f}, {hi:.3f}]")

# 6. SANITY CHECK: shuffled labels should score near chance (1/6 = 0.17). Not proof of no leakage. 
rf_s = pipe(RandomForestClassifier(n_estimators=200, class_weight="balanced", random_state=SEED, n_jobs=-1)).fit(Xtr, rng.permutation(ytr))
shuf = f1_score(yte, rf_s.predict(Xte), average="macro"); print(f"Shuffled-label check macro-F1: {shuf:.3f} (should be ~0.17)")
pd.DataFrame([dict(best_model=best_name, test_macro_f1=round(f1_score(yte, pred, average="macro"), 4),
                   ci_low=round(lo, 4), ci_high=round(hi, 4), shuffled_label_macro_f1=round(shuf, 4))]).to_csv(f"{OUT}/best_model_summary.csv", index=False)

# 7. FEATURE IMPORTANCE (random forest; association, not biological causation) 
rf = fitted["Random Forest"]
imp = pd.Series(rf.named_steps["clf"].feature_importances_, index=feature_names(rf.named_steps["genes"])).sort_values().tail(20)
fig, ax = plt.subplots(figsize=(6.5, 6)); imp.plot.barh(ax=ax, color="steelblue"); ax.set_title("Random Forest - top 20 features")
fig.tight_layout(); fig.savefig(f"{OUT}/feature_importance.png", dpi=200); plt.close(fig)

# 8. EXPORT COMPACT RANDOM FOREST AS JSON FOR THE WEB PORTAL (this is what the website runs, not the full forest)
web = pipe(RandomForestClassifier(n_estimators=60, max_depth=10, min_samples_leaf=3, class_weight="balanced",
                                  random_state=SEED, n_jobs=-1)).fit(Xtr, ytr)
sel, wrf = web.named_steps["genes"], web.named_steps["clf"]
pw, pp = web.predict(Xte), web.predict_proba(Xte)
web_metrics = dict(model=f"Random Forest (compact, exported) {MODEL_VERSION}", test_accuracy=round(accuracy_score(yte, pw), 4),
                   test_macro_f1=round(f1_score(yte, pw, average="macro"), 4),
                   test_auc_ovr=round(roc_auc_score(yte, pp, multi_class="ovr", average="macro", labels=wrf.classes_), 4),
                   n_test_patients=len(yte))
print(f"Compact web RF: test acc={web_metrics['test_accuracy']:.3f} macro-F1={web_metrics['test_macro_f1']:.3f}")
pd.DataFrame([web_metrics]).to_csv(f"{OUT}/web_model_performance.csv", index=False)


def tree_to_dict(t):
    leaf = t.children_left == -1
    v = t.value[:, 0, :]; v = v / v.sum(axis=1, keepdims=True)
    return {"left": t.children_left.tolist(), "right": t.children_right.tolist(), "feature": t.feature.tolist(),
            "threshold": t.threshold.tolist(),       # full precision: rounding could flip a comparison
            "value": [np.round(v[i], 6).tolist() if leaf[i] else None for i in range(t.node_count)]}


mj = {"model_version": MODEL_VERSION, "classes": list(wrf.classes_),
      "class_study_codes": {v: k for k, v in STUDIES.items()},
      "feature_names": feature_names(sel), "n_dense": N_DENSE, "subs": SUBS, "variant_classes": VCS,
      "genes": [CAND_GENES[i] for i in sel.keep_], "required_columns": REQUIRED, "dedup_key": DEDUP_KEY[1:],
      "metrics": web_metrics, "train_patients": sorted(tr_ids),
      "trees": [tree_to_dict(e.tree_) for e in wrf.estimators_]}
json.dump(mj, open(f"{OUT}/rf_model.json", "w"), separators=(",", ":"))


def pj(x):
    """Plain-Python traversal of the JSON trees (same algorithm as predict.js)."""
    tot = np.zeros(len(mj["classes"]))
    for t in mj["trees"]:
        n = 0
        while t["left"][n] != -1:
            n = t["left"][n] if x[t["feature"][n]] <= t["threshold"][n] else t["right"][n]
        tot += t["value"][n]
    return tot / len(mj["trees"])


Xte_sel = sel.transform(Xte).astype(np.float64)        # float32 values (as sklearn sees them), compared in float64
chk = np.array([pj(x) for x in Xte_sel])
print(f"JSON traversal vs sklearn on all {len(yte)} test patients: label agreement "
      f"{(chk.argmax(1) == pp.argmax(1)).mean() * 100:.1f}% | max score difference {np.abs(chk - pp).max():.2e}")

# 9. PARITY FIXTURES: raw TSV rows + Python features/scores, used by tests/predict_parity.test.js 
def python_predict(rows):
    d = prepare(rows); idx = pd.Index(d.patient.unique())
    Xs = sp.hstack([sp.csr_matrix(dense_features(d, idx).values), gene_matrix(d, idx, CAND_GENES)]).tocsr()
    return sel.transform(Xs)[0].astype(float), web.predict_proba(Xs)[0]


cases = []
def add_case(name, rows, note):
    feats, scores = python_predict(rows)
    tsv = rows[COLS].to_csv(sep="\t", index=False, lineterminator="\n")
    open(f"{OUT}/examples/{name}.tsv", "w", newline="").write(tsv)
    cases.append(dict(name=name, note=note, tsv=tsv, expected_features=feats.tolist(),
                      expected_scores=dict(zip(wrf.classes_, scores.round(9).tolist())),
                      expected_label=wrf.classes_[scores.argmax()]))
    return feats, scores

for cls in classes:                                     # one held-out TEST patient per cancer type
    pid = next(p for p in te_ids if pat.cancer[p] == cls)
    code = next(k for k, v in STUDIES.items() if v == cls)
    f, s = add_case(f"test_{code}_{pid}", raw[raw.patient == pid], f"Held-out test patient, true class {cls}")
    j = list(te_ids).index(pid)
    assert np.allclose(f, Xte_sel[j]) and np.allclose(s, pp[j]), f"fixture pipeline mismatch for {pid}"
dup = raw[raw.patient == te_ids[0]]
add_case("edge_duplicated_rows", pd.concat([dup, dup]), "Every row twice: de-duplication must give the original result")
syn = pd.DataFrame([
    ["FAKEGENE1", "GRCh37", "1", "1000", "1000", "G", "A", "Missense_Mutation", "SNP", "DEMO-UNKNOWN-01", "", ""],
    ["FAKEGENE2", "GRCh37", "2", "2000", "2001", "-", "AT", "Frame_Shift_Ins", "INS", "DEMO-UNKNOWN-01", "", ""],
    ["FAKEGENE3", "GRCh37", "3", "3000", "3002", "CTG", "-", "Weird_Class", "DEL", "DEMO-UNKNOWN-01", "", ""],
    ["FAKEGENE4", "GRCh37", "4", "4000", "4001", "AC", "GT", "Missense_Mutation", "DNP", "DEMO-UNKNOWN-01", "", ""],
], columns=COLS)
add_case("edge_unknown_genes", syn, "Genes not in the model, an unknown variant class, a strand flip (G>A -> C>T) and a DNP")
add_case("edge_single_mutation", pd.DataFrame([["TP53", "GRCh37", "17", "7579472", "7579472", "C", "T", "Missense_Mutation",
                                                "SNP", "MAN-EXAMPLE-01", "MODERATE", "p.R175H"]], columns=COLS),
         "Single TP53 mutation (same as the website upload_example.tsv)")
json.dump({"model_version": MODEL_VERSION, "feature_tolerance": 1e-6, "score_tolerance": 1e-4, "cases": cases},
          open(f"{OUT}/parity_fixtures.json", "w"), indent=1)

# 10. COPY MODEL TO THE WEBSITE + RUN SUMMARY 
if os.path.isdir(os.path.dirname(WEB_MODEL_DIR)):
    os.makedirs(WEB_MODEL_DIR, exist_ok=True)
    shutil.copy(f"{OUT}/rf_model.json", WEB_MODEL_DIR)
    print("Copied rf_model.json to", WEB_MODEL_DIR)
json.dump(dict(model_version=MODEL_VERSION, raw_rows=len(raw), rows_after_dedup=len(m), patients=len(pat),
               train_patients=len(tr_ids), test_patients=len(te_ids), dense_features=N_DENSE, genes=TOP_GENES,
               candidate_genes=len(CAND_GENES), best_model_by_cv=best_name, runtime_s=round(time.time() - t0)),
          open(f"{OUT}/run_summary.json", "w"), indent=1)
print("JSON size (MB):", round(os.path.getsize(f"{OUT}/rf_model.json") / 1e6, 2), "| DONE in", round(time.time() - t0), "s")
