import pickle, os, json

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model.pkl")
if not os.path.exists(MODEL_PATH):
    print("MISSING model.pkl")
    raise SystemExit(1)
with open(MODEL_PATH, "rb") as f:
    data = pickle.load(f)
model = data.get("model")
encoder = data.get("encoder")
print("Model type:", type(model))
try:
    print("Classes (encoder):", encoder.classes_.tolist())
except Exception as e:
    print("Encoder issue:", e)
try:
    print("Model classes_: ", getattr(model, "classes_", None))
except:
    pass
# Show some model info
try:
    print("Model params:", model.get_params())
except Exception:
    pass
# Test predict_proba on representative inputs
X = [[130, 20, 10], [30, 130, 20], [20, 30, 140], [300, 900, 35], [50, 100, 60]]
print("\nTest inputs:")
for x in X:
    try:
        pred = model.predict([x])[0]
        proba = model.predict_proba([x])[0]
        print(
            x,
            "->",
            pred,
            "proba(max):",
            round(max(proba), 4),
            "proba:",
            [round(p, 4) for p in proba],
        )
    except Exception as e:
        print("Error for", x, e)
# Count leaves or something
try:
    from sklearn.tree import DecisionTreeClassifier

    if isinstance(model, DecisionTreeClassifier):
        print("Tree depth:", model.get_depth(), "n_leaves:", model.get_n_leaves())
except Exception:
    pass
# Print class distribution in dataset.csv
csvp = os.path.join(os.path.dirname(__file__), "dataset.csv")
if os.path.exists(csvp):
    from collections import Counter

    with open(csvp, "r", encoding="utf-8") as f:
        cnt = Counter()
        next(f)
        for line in f:
            line = line.strip()
            if not line:
                continue
            parts = line.rsplit(",", 1)
            if len(parts) == 2:
                major = parts[1].strip().strip('"')
                cnt[major] += 1
    print("\nDataset counts:")
    for k, v in cnt.most_common():
        print(k, v)
else:
    print("no dataset.csv")
with open(
    os.path.join(os.path.dirname(__file__), "inspect_model_out.json"), "w"
) as out:
    json.dump({"ok": True}, out)
print("\nWROTE inspect_model_out.json")
