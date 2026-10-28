
"""
BIOM9450 Major Project - cancer type prediction model pipeline prototype 
Usage:  
* python train_model.py 
* tcga_data datasets 

written by Christian Schmooel (z5417031), with AI-assistance for ideation and debugging (Claude Sonnet 5.5)

!! PROTOTYPE: Used as practice exercise following discussion with course convenor

"""
import sys, json, time
import numpy as np, pandas as pd
import matplotlib; matplotlib.use("Agg")
import matplotlib.pyplot as plt
from sklearn.model_selection import train_test_split, GridSearchCV, GroupKFold
from sklearn.dummy import DummyClassifier
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier
from sklearn.svm import SVC
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (accuracy_score, precision_recall_fscore_support, f1_score,
                             confusion_matrix, ConfusionMatrixDisplay, classification_report)
import os

SEED = 42
PROFILE_SIZE = 500  # mutations per simulated patient profile
TRAIN_PER_CLASS_PER_FOLD = 100
N_FOLDS = 5
TEST_PER_CLASS = 200
OUT = "model_output"
os.makedirs(OUT, exist_ok=True)
rng = np.random.default_rng(SEED)
t0 = time.time()

# 1. LOAD + CLEAN DATA 
cols = ["chrom","start","end","mut_type","ref","alt","consequence","gene","cancer"]
df = pd.read_csv(sys.argv[1], header=None, names=cols, dtype=str, low_memory=False)
print("rows loaded:", len(df))
df = df.drop_duplicates().reset_index(drop=True)
print("rows after removing duplicates:", len(df))

df["sub"] = np.where(df.mut_type.str.startswith("single"), df.ref + ">" + df.alt,
                     np.where(df.mut_type.str.startswith("deletion"), "DEL", "INS"))
top_cons = df.consequence.value_counts().index[:10].tolist()
df["cons"] = df.consequence.where(df.consequence.isin(top_cons), "other")
df["has_gene"] = df.gene.notna().astype(int)

sub_cats  = sorted(df["sub"].unique())
cons_cats = top_cons + ["other"]
chr_cats  = [str(i) for i in range(1, 23)] + ["X", "Y", "MT"]
feature_names = (["sub_" + s for s in sub_cats] + ["cons_" + c for c in cons_cats]
                 + ["chr_" + c for c in chr_cats] + ["has_gene"])

# one-hot matrix per mutation; a profiles features = the mean of its rows
def onehot(col, cats, prefix):
    return np.column_stack([(df[col] == c).values for c in cats]).astype(np.float32)
M = np.hstack([onehot("sub", sub_cats, "sub_"), onehot("cons", cons_cats, "cons_"),
               onehot("chrom", chr_cats, "chr_"), df[["has_gene"]].values.astype(np.float32)])
labels = df.cancer.values
classes = sorted(df.cancer.unique())
print("classes:", classes, "| features:", M.shape[1])

# 2. SPLIT MUTATIONS FIRST -> prevent leakage 
idx = np.arange(len(df))
train_idx, test_idx = train_test_split(idx, test_size=0.30, stratify=labels, random_state=SEED)
fold_of = np.full(len(df), -1)
fold_of[train_idx] = rng.integers(0, N_FOLDS, size=len(train_idx))

# 3. BUILD SIMULATED PATIENT PROFILES 
def make_profiles(pool_idx, per_class, size):
    X, y = [], []
    for c in classes:
        pool = pool_idx[labels[pool_idx] == c]
        for _ in range(per_class):
            pick = rng.choice(pool, size=size, replace=False)
            X.append(M[pick].mean(axis=0)); y.append(c)
    return np.array(X), np.array(y)

Xtr, ytr, gtr = [], [], []
for f in range(N_FOLDS):
    Xf, yf = make_profiles(train_idx[fold_of[train_idx] == f], TRAIN_PER_CLASS_PER_FOLD, PROFILE_SIZE)
    Xtr.append(Xf); ytr.append(yf); gtr += [f] * len(yf)
Xtr, ytr, gtr = np.vstack(Xtr), np.concatenate(ytr), np.array(gtr)
Xte, yte = make_profiles(test_idx, TEST_PER_CLASS, PROFILE_SIZE)
print("train profiles:", Xtr.shape, "test profiles:", Xte.shape)

# 4. MODELS + TUNING (grouped CV: folds never share mutations)
cv = GroupKFold(n_splits=N_FOLDS)
candidates = {
  "Baseline (majority)": (DummyClassifier(strategy="most_frequent"), {}),
  "Decision Tree": (DecisionTreeClassifier(random_state=SEED),
                    {"max_depth": [3, 5, 8, None], "min_samples_leaf": [1, 5]}),
  "Random Forest": (RandomForestClassifier(random_state=SEED, n_jobs=-1),
                    {"n_estimators": [100, 200], "max_depth": [8, 15], "min_samples_leaf": [1, 3]}),
  "SVM (RBF)": (make_pipeline(StandardScaler(), SVC(probability=True, random_state=SEED)),
                {"svc__C": [1, 10], "svc__gamma": ["scale", 0.01]}),
}
results, fitted = [], {}
for name, (est, grid) in candidates.items():
    if grid:
        gs = GridSearchCV(est, grid, cv=cv, scoring="f1_macro", n_jobs=-1)
        gs.fit(Xtr, ytr, groups=gtr); best, cvscore, params = gs.best_estimator_, gs.best_score_, gs.best_params_
    else:
        from sklearn.model_selection import cross_val_score
        cvscore = cross_val_score(est, Xtr, ytr, cv=cv, groups=gtr, scoring="f1_macro").mean()
        best, params = est.fit(Xtr, ytr), {}
    pred = best.predict(Xte)
    p, r, f1, _ = precision_recall_fscore_support(yte, pred, average="macro", zero_division=0)
    results.append(dict(model=name, cv_macro_f1=round(cvscore,4), test_accuracy=round(accuracy_score(yte,pred),4),
                        test_precision=round(p,4), test_recall=round(r,4), test_macro_f1=round(f1,4), best_params=str(params)))
    fitted[name] = best
    print(f"{name}: CV F1={cvscore:.3f} test acc={accuracy_score(yte,pred):.3f}  ({time.time()-t0:.0f}s)", flush=True)

res = pd.DataFrame(results); res.to_csv(f"{OUT}/model_comparison.csv", index=False)

# 5. EVALUATE BEST MODEL 
best_name = res.sort_values("cv_macro_f1", ascending=False).iloc[0].model
best = fitted[best_name]
pred = best.predict(Xte)
with open(f"{OUT}/classification_report.txt", "w") as fh:
    fh.write(f"Best model by CV macro-F1: {best_name}\n\n" + classification_report(yte, pred, digits=3))
fig, ax = plt.subplots(figsize=(6.5, 5.5))
ConfusionMatrixDisplay(confusion_matrix(yte, pred, labels=classes), display_labels=classes).plot(ax=ax, cmap="Blues", xticks_rotation=45, colorbar=False)
ax.set_title(f"Confusion matrix - {best_name}"); fig.tight_layout(); fig.savefig(f"{OUT}/confusion_matrix.png", dpi=200); plt.close(fig)

rf = fitted["Random Forest"]
imp = pd.Series(rf.feature_importances_, index=feature_names).sort_values().tail(15)
fig, ax = plt.subplots(figsize=(6.5, 5)); imp.plot.barh(ax=ax, color="steelblue")
ax.set_title("Random Forest - top 15 features"); fig.tight_layout(); fig.savefig(f"{OUT}/feature_importance.png", dpi=200); plt.close(fig)

# accuracy vs number of mutations in the profile
sizes = [25, 50, 100, 200, 500, 1000]; accs = []
for s in sizes:
    Xs, ys = make_profiles(test_idx, 60, s); accs.append(accuracy_score(ys, best.predict(Xs)))
fig, ax = plt.subplots(figsize=(6, 4)); ax.plot(sizes, accs, "o-"); ax.set_xscale("log")
ax.axhline(1/len(classes), ls="--", c="grey", label="chance"); ax.set_xlabel("Mutations per profile"); ax.set_ylabel("Test accuracy")
ax.set_title("Accuracy vs profile size"); ax.legend(); fig.tight_layout(); fig.savefig(f"{OUT}/accuracy_vs_profile_size.png", dpi=200); plt.close(fig)
pd.DataFrame({"mutations_per_profile": sizes, "accuracy": np.round(accs,4)}).to_csv(f"{OUT}/accuracy_vs_profile_size.csv", index=False)

# 6. EXPORT A COMPACT RANDOM FOREST TO JSON FOR THE WEB PORTAL INTERFACE
rf_full = rf
rf = RandomForestClassifier(n_estimators=60, max_depth=10, min_samples_leaf=5, random_state=SEED, n_jobs=-1).fit(Xtr, ytr)
pc = rf.predict(Xte)
print(f"Compact web RF: test acc={accuracy_score(yte,pc):.3f} macro-F1={f1_score(yte,pc,average='macro'):.3f}")
pd.DataFrame([dict(model="Random Forest (compact, exported)", test_accuracy=round(accuracy_score(yte,pc),4),
  test_macro_f1=round(f1_score(yte,pc,average='macro'),4))]).to_csv(f"{OUT}/web_model_performance.csv", index=False)
def tree_to_dict(t):
    v = t.value[:, 0, :]; v = v / v.sum(axis=1, keepdims=True)
    return {"left": t.children_left.tolist(), "right": t.children_right.tolist(),
            "feature": t.feature.tolist(), "threshold": np.round(t.threshold, 9).tolist(),
            "value": np.round(v, 4).tolist()}
model_json = {"classes": list(rf.classes_), "feature_names": feature_names,
              "categories": {"sub": sub_cats, "cons": cons_cats, "chrom": chr_cats},
              "profile_size_trained": PROFILE_SIZE, "min_recommended_mutations": 100,
              "trees": [tree_to_dict(e.tree_) for e in rf.estimators_]}
json.dump(model_json, open(f"{OUT}/rf_model.json", "w"))

def predict_json(m, x): # same logic the JavaScript will use
    tot = np.zeros(len(m["classes"]))
    for t in m["trees"]:
        n = 0
        while t["left"][n] != -1:
            n = t["left"][n] if x[t["feature"][n]] <= t["threshold"][n] else t["right"][n]
        tot += t["value"][n]
    return tot / len(m["trees"])
chk = np.array([predict_json(model_json, x) for x in Xte[:100]])
agree = (np.array(rf.classes_)[chk.argmax(1)] == rf.predict(Xte[:100])).mean()
print("JSON model agrees with sklearn on", agree*100, "% of 100 test profiles")
print("DONE", round(time.time()-t0), "s")
