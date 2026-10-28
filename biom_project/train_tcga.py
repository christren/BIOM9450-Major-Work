
"""
BIOM9450 Major Project: patient-level cancer type prediction (via TCGA PanCancer Atlas datasets)
Usage:  
* python train_tcga.py - run from biom_project, data in tcga_data/<studycode>/data_mutations.txt

"""
import json, os, time, warnings
import numpy as np, pandas as pd
import matplotlib; matplotlib.use("Agg")
import matplotlib.pyplot as plt
from sklearn.model_selection import train_test_split, StratifiedKFold, GridSearchCV
from sklearn.dummy import DummyClassifier
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.svm import SVC
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (accuracy_score, balanced_accuracy_score, f1_score, precision_score, recall_score,
                             roc_auc_score, confusion_matrix, ConfusionMatrixDisplay, classification_report)
warnings.filterwarnings("ignore")

SEED, TOP_GENES, N_VC, TEST_FRAC, DATA, OUT = 42, 300, 12, 0.20, "tcga_data", "model_output_tcga"
STUDIES = {"laml": "Blood", "lgg": "Brain", "brca": "Breast", "lihc": "Liver", "paad": "Pancreas", "prad": "Prostate"}
os.makedirs(OUT, exist_ok=True)
t0 = time.time()
rng = np.random.default_rng(SEED)

# 1. LOAD: one table of mutations, each with a patient ID and cancer label ------------------
use = ["Hugo_Symbol", "Variant_Classification", "Variant_Type", "Reference_Allele", "Tumor_Seq_Allele2", "Tumor_Sample_Barcode"]
parts = []
for code, name in STUDIES.items():
    d = pd.read_csv(f"{DATA}/{code}/data_mutations.txt", sep="\t", comment="#", usecols=use, dtype=str, low_memory=False)
    d["cancer"] = name
    parts.append(d)
    print(f"{code}: {len(d)} mutations, {d.Tumor_Sample_Barcode.str[:12].nunique()} patients")
m = pd.concat(parts, ignore_index=True)
m["patient"] = m.Tumor_Sample_Barcode.str[:12]            # TCGA-XX-YYYY = one patient
m = m.drop_duplicates(["patient", "Hugo_Symbol", "Variant_Classification", "Reference_Allele", "Tumor_Seq_Allele2"])
m = m[~m.patient.isin(m.groupby("patient").cancer.nunique().loc[lambda s: s > 1].index)]   # drop patients in 2 classes

# 2. PER-MUTATION LABELS: substitution class + variant class --------------------------------
comp = {"A": "T", "T": "A", "C": "G", "G": "C"}
ref, alt = m.Reference_Allele, m.Tumor_Seq_Allele2
snp = (m.Variant_Type == "SNP") & ref.isin(list(comp)) & alt.isin(list(comp))
flip = snp & ref.isin(["A", "G"])                          # express every SNP on the C/T strand
r2 = np.where(flip, ref.map(comp), ref); a2 = np.where(flip, alt.map(comp), alt)
m["sub"] = np.where(snp, pd.Series(r2, index=m.index) + ">" + pd.Series(a2, index=m.index),
                    np.where(m.Variant_Type.isin(["INS", "DEL"]), "indel", "other"))
vc_list = m.Variant_Classification.value_counts().index[:N_VC].tolist()
m["vc"] = m.Variant_Classification.where(m.Variant_Classification.isin(vc_list), "other_vc")
SUBS = ["C>A", "C>G", "C>T", "T>A", "T>C", "T>G", "indel", "other"]
VCS = vc_list + ["other_vc"]

# 3. PATIENT TABLE + SPLIT BY PATIENT (before any feature selection) ------------------------
pat = m.groupby("patient").agg(cancer=("cancer", "first"), n_mut=("Hugo_Symbol", "size"))
print("\npatients per class:\n", pat.cancer.value_counts().to_string())
tr_ids, te_ids = train_test_split(pat.index, test_size=TEST_FRAC, stratify=pat.cancer, random_state=SEED)
pd.DataFrame({"patient": list(tr_ids) + list(te_ids), "set": ["train"] * len(tr_ids) + ["test"] * len(te_ids)}).to_csv(f"{OUT}/patient_split.csv", index=False)

# 4. FEATURES: log mutation count, substitution %, variant-class %, top genes (0/1) ---------
dense = pd.DataFrame(index=pat.index)
dense["log_n_mut"] = np.log1p(pat.n_mut)
for col, cats, pre in [("sub", SUBS, "sub_"), ("vc", VCS, "vc_")]:
    ct = pd.crosstab(m.patient, m[col], normalize="index").reindex(index=pat.index, columns=cats, fill_value=0)
    ct.columns = [pre + c for c in cats]; dense = dense.join(ct)
gene_freq = m[m.patient.isin(tr_ids)].drop_duplicates(["patient", "Hugo_Symbol"]).Hugo_Symbol.value_counts()   # TRAIN patients only, no labels used
GENES = gene_freq.index[:TOP_GENES].tolist()
g = m[m.Hugo_Symbol.isin(GENES)].drop_duplicates(["patient", "Hugo_Symbol"])
gm = pd.crosstab(g.patient, g.Hugo_Symbol).reindex(index=pat.index, columns=GENES, fill_value=0).clip(upper=1)
gm.columns = ["gene_" + c for c in GENES]
X = dense.join(gm); feature_names = X.columns.tolist(); y = pat.cancer
Xtr, ytr, Xte, yte = X.loc[tr_ids].values, y.loc[tr_ids].values, X.loc[te_ids].values, y.loc[te_ids].values
classes = sorted(y.unique())
print(f"\nfeatures: {X.shape[1]} | train patients: {len(tr_ids)} | test patients: {len(te_ids)}")

# 5. MODELS + TUNING (stratified 5-fold CV on TRAIN only, scored by macro-F1) ----------------
cv = StratifiedKFold(5, shuffle=True, random_state=SEED)
cands = {
  "Baseline (majority)": (DummyClassifier(strategy="most_frequent"), {}),
  "Decision Tree": (DecisionTreeClassifier(class_weight="balanced", random_state=SEED),
                    {"max_depth": [4, 8, None], "min_samples_leaf": [1, 5]}),
  "Random Forest": (RandomForestClassifier(class_weight="balanced", random_state=SEED, n_jobs=-1),
                    {"n_estimators": [200, 400], "max_depth": [10, None], "min_samples_leaf": [1, 3]}),
  "SVM (RBF)": (make_pipeline(StandardScaler(), SVC(class_weight="balanced", probability=True, random_state=SEED)),
                {"svc__C": [1, 10], "svc__gamma": ["scale", 0.001]}),
}
rows, fitted = [], {}
for name, (est, grid) in cands.items():
    if grid:
        gs = GridSearchCV(est, grid, cv=cv, scoring="f1_macro", n_jobs=-1).fit(Xtr, ytr)
        best, cvf1, params = gs.best_estimator_, gs.best_score_, gs.best_params_
    else:
        from sklearn.model_selection import cross_val_score
        cvf1, params = cross_val_score(est, Xtr, ytr, cv=cv, scoring="f1_macro").mean(), {}
        best = est.fit(Xtr, ytr)
    p = best.predict(Xte)
    auc = roc_auc_score(yte, best.predict_proba(Xte), multi_class="ovr", average="macro", labels=best.classes_) if hasattr(best, "predict_proba") else np.nan
    rows.append(dict(model=name, cv_macro_f1=round(cvf1, 4), test_accuracy=round(accuracy_score(yte, p), 4),
                     test_balanced_acc=round(balanced_accuracy_score(yte, p), 4),
                     test_macro_precision=round(precision_score(yte, p, average="macro", zero_division=0), 4),
                     test_macro_recall=round(recall_score(yte, p, average="macro", zero_division=0), 4),
                     test_macro_f1=round(f1_score(yte, p, average="macro"), 4), test_auc_ovr=round(auc, 4), best_params=str(params)))
    fitted[name] = best
    print(f"{name}: CV macro-F1={cvf1:.3f} | test acc={rows[-1]['test_accuracy']:.3f} macro-F1={rows[-1]['test_macro_f1']:.3f} ({time.time()-t0:.0f}s)", flush=True)
res = pd.DataFrame(rows); res.to_csv(f"{OUT}/model_comparison.csv", index=False)

# 6. BEST MODEL: report, confusion matrices, bootstrap CI --------------------------------------
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

# 7. SANITY CHECK: shuffled labels should score near chance (1/6 = 0.17) -----------------------
rf_s = RandomForestClassifier(n_estimators=200, class_weight="balanced", random_state=SEED, n_jobs=-1).fit(Xtr, rng.permutation(ytr))
shuf = f1_score(yte, rf_s.predict(Xte), average="macro"); print(f"Shuffled-label check macro-F1: {shuf:.3f} (should be ~0.17)")
pd.DataFrame([dict(best_model=best_name, test_macro_f1=round(f1_score(yte, pred, average="macro"), 4),
                   ci_low=round(lo, 4), ci_high=round(hi, 4), shuffled_label_macro_f1=round(shuf, 4))]).to_csv(f"{OUT}/best_model_summary.csv", index=False)

# 8. FEATURE IMPORTANCE (random forest) ---------------------------------------------------------
rf = fitted["Random Forest"]; imp = pd.Series(rf.feature_importances_, index=feature_names).sort_values().tail(20)
fig, ax = plt.subplots(figsize=(6.5, 6)); imp.plot.barh(ax=ax, color="steelblue"); ax.set_title("Random Forest - top 20 features")
fig.tight_layout(); fig.savefig(f"{OUT}/feature_importance.png", dpi=200); plt.close(fig)

# 9. EXPORT COMPACT RANDOM FOREST AS JSON FOR THE WEB PORTAL --------------------------------------
web = RandomForestClassifier(n_estimators=60, max_depth=10, min_samples_leaf=3, class_weight="balanced", random_state=SEED, n_jobs=-1).fit(Xtr, ytr)
pw = web.predict(Xte); print(f"Compact web RF: test acc={accuracy_score(yte, pw):.3f} macro-F1={f1_score(yte, pw, average='macro'):.3f}")
pd.DataFrame([dict(model="Random Forest (compact, exported)", test_accuracy=round(accuracy_score(yte, pw), 4),
                   test_macro_f1=round(f1_score(yte, pw, average="macro"), 4))]).to_csv(f"{OUT}/web_model_performance.csv", index=False)
def tree_to_dict(t):
    v = t.value[:, 0, :]; v = v / v.sum(axis=1, keepdims=True)
    return {"left": t.children_left.tolist(), "right": t.children_right.tolist(), "feature": t.feature.tolist(),
            "threshold": np.round(t.threshold, 9).tolist(), "value": np.round(v, 4).tolist()}
mj = {"classes": list(web.classes_), "feature_names": feature_names, "subs": SUBS, "variant_classes": VCS, "genes": GENES,
      "input_columns": use[:-1] + ["Tumor_Sample_Barcode"], "trees": [tree_to_dict(e.tree_) for e in web.estimators_]}
json.dump(mj, open(f"{OUT}/rf_model.json", "w"))
def pj(x):
    tot = np.zeros(len(mj["classes"]))
    for t in mj["trees"]:
        n = 0
        while t["left"][n] != -1: n = t["left"][n] if x[t["feature"][n]] <= t["threshold"][n] else t["right"][n]
        tot += t["value"][n]
    return tot / len(mj["trees"])
chk = np.array([pj(x.astype(np.float32).astype(float)) for x in Xte[:200]])
print("JSON model agrees with sklearn on", round((np.array(web.classes_)[chk.argmax(1)] == web.predict(Xte[:200])).mean() * 100, 1), "% of test patients")
print("JSON size (MB):", round(os.path.getsize(f"{OUT}/rf_model.json") / 1e6, 2), "| DONE in", round(time.time() - t0), "s")
