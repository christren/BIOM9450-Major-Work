import pandas as pd, os
for c in ["laml","lgg","brca","lihc","paad","prad"]:
    p = f"tcga_data/{c}/data_mutations.txt"
    if not os.path.exists(p):
        print(c, "MISSING"); continue
    d = pd.read_csv(p, sep="\t", comment="#", low_memory=False)
    print(c, d.shape, "samples:", d["Tumor_Sample_Barcode"].nunique())
    if c == "laml":
        print(list(d.columns))
        print(d[["Hugo_Symbol","Variant_Classification","Variant_Type","Tumor_Sample_Barcode"]].head())