#!/usr/bin/env python3
"""
Train a RandomForest model on php/ai/dataset.csv and save php/ai/model.pkl.
This script assumes `dataset.csv` has header: part1_score,part2_score,part3_score,major_name

Usage:
  python train_balanced.py

Outputs:
  - model.pkl (contains {'model': model, 'encoder': encoder})
  - train_log.txt with counts and basic metrics
"""
import os
import csv
import pickle
from collections import Counter
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import LabelEncoder
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report

ROOT = os.path.dirname(__file__)
DATA_CSV = os.path.join(ROOT, "dataset.csv")
MODEL_PATH = os.path.join(ROOT, "model.pkl")
LOG_PATH = os.path.join(ROOT, "train_log.txt")


def read_dataset(path):
    X = []
    y = []
    if not os.path.exists(path):
        return X, y
    with open(path, newline="", encoding="utf-8") as f:
        rdr = csv.reader(f)
        header = next(rdr, None)
        for r in rdr:
            if len(r) < 4:
                continue
            try:
                p1 = float(r[0])
                p2 = float(r[1])
                p3 = float(r[2])
            except Exception:
                p1 = p2 = p3 = 0.0
            X.append([p1, p2, p3])
            y.append(r[3].strip())
    return X, y


def main():
    X, y = read_dataset(DATA_CSV)
    if not X:
        print("No dataset found at", DATA_CSV)
        return

    le = LabelEncoder()
    y_enc = le.fit_transform(y)

    # Use a held-out test split for quick verification
    X_train, X_test, y_train, y_test = train_test_split(
        X, y_enc, test_size=0.2, random_state=42, stratify=y_enc
    )

    model = RandomForestClassifier(n_estimators=300, random_state=42, n_jobs=-1)
    model.fit(X_train, y_train)

    # evaluate
    preds = model.predict(X_test)
    report = classification_report(
        y_test, preds, target_names=le.classes_, zero_division=0
    )

    # save model + encoder as a dict for loader convenience
    with open(MODEL_PATH, "wb") as f:
        pickle.dump({"model": model, "encoder": le}, f)

    # write log
    with open(LOG_PATH, "w", encoding="utf-8") as f:
        f.write("Training complete\n")
        f.write(f"Samples: {len(X)}\n")
        f.write("Class distribution:\n")
        f.write(str(Counter(y)) + "\n")
        f.write("\nEvaluation:\n")
        f.write(report)

    print("Training finished. Model saved to", MODEL_PATH)


if __name__ == "__main__":
    main()
