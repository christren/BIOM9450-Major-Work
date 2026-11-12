"""Create a small TCGA subset for the demonstration database.

Install: python -m pip install pandas
Run from anywhere: python biom_project/sample_tcga_for_database.py
Default input: tcga_data beside this script.
Output: tcga_database_subset.zip beside this script. Upload that ZIP to chat.
This does not create MySQL tables or change training data/models.
"""
import argparse
import hashlib
import json
from pathlib import Path
import random
import zipfile
import pandas as pd

STUDIES = {
    'laml': 'Blood (AML)', 'lgg': 'Brain (LGG)',
    'brca': 'Breast', 'lihc': 'Liver',
    'paad': 'Pancreas', 'prad': 'Prostate',
}
REQUIRED = [
    'Tumor_Sample_Barcode', 'Hugo_Symbol', 'Chromosome',
    'Start_Position', 'End_Position', 'Reference_Allele',
    'Tumor_Seq_Allele2', 'Variant_Classification', 'Variant_Type',
]
OPTIONAL = ['NCBI_Build', 'IMPACT', 'HGVSp_Short']

def main():
    base = Path(__file__).resolve().parent
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--data-dir', type=Path, default=base / 'tcga_data')
    parser.add_argument('--output', type=Path, default=base / 'tcga_database_subset.zip')
    parser.add_argument('--patients', type=int, default=50)
    parser.add_argument('--seed', type=int, default=42)
    args = parser.parse_args()
    if args.patients < 1:
        parser.error('--patients must be positive')
    if args.output.exists():
        parser.error(f'Output already exists: {args.output}. Move it or use --output.')

    mutations, patients, samples, manifest = [], [], [], []
    seen = set()
    for code, label in STUDIES.items():
        source = args.data_dir / code / 'data_mutations.txt'
        if not source.is_file():
            raise FileNotFoundError(f'Missing {source}; extract all six study folders first.')
        header = pd.read_csv(source, sep='\t', comment='#', nrows=0).columns
        missing = sorted(set(REQUIRED) - set(header))
        if missing:
            raise ValueError(f'{code}: missing columns {missing}')
        columns = REQUIRED + [c for c in OPTIONAL if c in header]
        df = pd.read_csv(source, sep='\t', comment='#', usecols=columns,
                         dtype=str, keep_default_na=False)
        barcodes = df['Tumor_Sample_Barcode'].str.strip()
        valid = barcodes.str.match(r'^TCGA-[A-Za-z0-9]{2}-[A-Za-z0-9]{4}(?:-|$)')
        if not valid.all():
            raise ValueError(f'{code}: invalid or missing TCGA barcode; no rows silently discarded.')
        df['Tumor_Sample_Barcode'] = barcodes
        df['TCGAPatientID'] = barcodes.str[:12]
        ids = sorted(df['TCGAPatientID'].unique())
        if len(ids) < args.patients:
            raise ValueError(f'{code}: only {len(ids)} patients available.')
        selected = sorted(random.Random(f'{args.seed}:{code}').sample(ids, args.patients))
        if seen.intersection(selected):
            raise ValueError('Patient occurs in multiple studies; review labels before sampling.')
        seen.update(selected)
        subset = df[df['TCGAPatientID'].isin(selected)].copy()
        subset['StudyCode'] = code
        subset['CancerLabel'] = label
        mutations.append(subset)
        patients.extend({'TCGAPatientID': p, 'StudyCode': code, 'CancerLabel': label}
                        for p in selected)
        sample = subset[['TCGAPatientID', 'Tumor_Sample_Barcode', 'StudyCode']].drop_duplicates()
        samples.append(sample)
        digest = hashlib.sha256()
        with source.open('rb') as handle:
            for block in iter(lambda: handle.read(1024 * 1024), b''):
                digest.update(block)
        manifest.append({
            'study': code, 'cancer': label, 'source_rows': len(df),
            'source_patients': len(ids), 'selected_patients': len(selected),
            'selected_samples': len(sample), 'selected_mutation_rows': len(subset),
            'retained_columns': list(subset.columns),
            'source_sha256': digest.hexdigest(),
        })
        print(f'{code}: {len(selected)} patients, {len(sample)} samples, {len(subset)} mutation rows')

    combined = pd.concat(mutations, ignore_index=True).fillna('')
    combined = combined.sort_values(
        ['StudyCode', 'TCGAPatientID', 'Tumor_Sample_Barcode', 'Chromosome',
         'Start_Position', 'End_Position', 'Reference_Allele', 'Tumor_Seq_Allele2',
         'Hugo_Symbol'], kind='stable')
    metadata = {
        'seed': args.seed, 'patients_per_cancer': args.patients,
        'total_patients': len(patients), 'studies': manifest,
        'sampling': 'Uniform sampling of sorted unique 12-character patient IDs per study; all their mutation rows retained.',
        'duplicates': 'Original rows retained; variant identity must include genome build, chromosome, position and alleles when normalising.',
        'clinical_data': 'No clinical records or fictional demographics included. Those can be added as explicitly synthetic demo fields.',
        'purpose': 'Portal demonstration subset only; not a replacement training or independent validation dataset.',
    }
    readme = (
        '# TCGA database subset\n\n'
        'Contains real mutation records for randomly selected patients from six training studies.\n'
        'All mutation rows for selected patients are retained; samples remain separate.\n'
        'patients.csv: TCGA patient identifiers and cancer labels.\n'
        'samples.csv: sample-to-patient links.\n'
        'mutations.tsv: required mutation, location, allele and model-input fields.\n'
        'manifest.json: seed, record counts and original source checksums.\n\n'
        'No names, ages, sexes, dates, treatments or login credentials are fabricated here.\n'
        'Any synthetic display details added later must be labelled as such.\n'
        'This ZIP is input for building a MySQL SQL import, not a database file.\n'
        'Keep the original full training datasets and model unchanged.\n'
    )
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(args.output, 'x', compression=zipfile.ZIP_DEFLATED) as archive:
        archive.writestr('patients.csv', pd.DataFrame(patients).to_csv(index=False))
        archive.writestr('samples.csv', pd.concat(samples, ignore_index=True).to_csv(index=False))
        archive.writestr('mutations.tsv', combined.to_csv(index=False, sep='\t'))
        archive.writestr('manifest.json', json.dumps(metadata, indent=2))
        archive.writestr('README.md', readme)
    with zipfile.ZipFile(args.output) as archive:
        if archive.testzip() is not None:
            raise RuntimeError('ZIP integrity check failed')
    print(f'Created {args.output} ({args.output.stat().st_size / 1024**2:.2f} MB)')
    print('Upload this ZIP to chat. Original datasets were not modified.')

if __name__ == '__main__':
    main()
