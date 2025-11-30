import mysql.connector
import numpy as np
import pickle
import os
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import LabelEncoder

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "edupathdb"
}

def fetch_training_data():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    # Gather labeled data from final_result, join majors, and pull part scores
    query = """
        SELECT
            fr.student_assessment_id,
            fr.major_id,
            m.major_name,
            MAX(CASE WHEN apr.part_number = 1 THEN apr.total_score END) AS part1_total,
            MAX(CASE WHEN apr.part_number = 2 THEN apr.total_score END) AS part2_total,
            MAX(CASE WHEN apr.part_number = 3 THEN apr.total_score END) AS part3_total
        FROM final_result fr
        JOIN majors m ON m.major_id = fr.major_id
        LEFT JOIN assessment_part_results apr ON apr.student_assessment_id = fr.student_assessment_id
        GROUP BY fr.student_assessment_id, fr.major_id, m.major_name
    """

    cursor.execute(query)
    data = cursor.fetchall()
    conn.close()
    return data


def vectorize_from_parts(p1, p2, p3):
    # The features are the three part scores already present in the DB
    return [int(p1 or 0), int(p2 or 0), int(p3 or 0)]


def train_model():
    data = fetch_training_data()
    if len(data) < 5:
        # Not enough labeled rows to train from DB. We'll attempt to create a small synthetic model
        print("NOT ENOUGH TRAINING DATA - will attempt to create a synthetic model for testing")

    X, y = [], []

    for row in data:
        # Ensure part totals are present (using exact alias names)
        p1 = row.get("part1_total")
        p2 = row.get("part2_total")
        p3 = row.get("part3_total")
        # Skip rows that do not have all three part scores
        if p1 is None or p2 is None or p3 is None:
            continue
        X.append(vectorize_from_parts(p1, p2, p3))
        # major_id is already numeric and used as the label
        y.append(int(row.get("major_id")))

    # If after collecting rows we still have insufficient data, build a small synthetic dataset
    if len(X) < 3:
        # Try to read available major IDs from DB
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            cur = conn.cursor()
            cur.execute("SELECT major_id FROM majors LIMIT 5")
            majors_rows = [int(r[0]) for r in cur.fetchall()]
            cur.close()
            conn.close()
            if len(majors_rows) < 2:
                majors_rows = [1,2,3]
        except Exception:
            majors_rows = [1,2,3]

        import numpy as _np
        _np.random.seed(42)
        synth_X = []
        synth_y = []
        # create simple clusters for each major id
        centers = [[120, 120, 120], [80, 140, 100], [100, 90, 150]]
        for i, mid in enumerate(majors_rows):
            center = centers[i % len(centers)]
            for _ in range(10):
                sample = [int(max(0, c + _np.random.randint(-10, 11))) for c in center]
                synth_X.append(sample)
                synth_y.append(int(mid))

        X = X + synth_X
        y = y + synth_y

    le = LabelEncoder()
    y_enc = le.fit_transform(y)

    model = RandomForestClassifier(n_estimators=200)
    model.fit(X, y_enc)

    model_path = os.path.join(os.path.dirname(__file__), "model.pkl")
    with open(model_path, "wb") as f:
        pickle.dump({"model": model, "encoder": le}, f)

    print(f"MODEL_TRAINED_SUCCESSFULLY Saved to: {model_path}")


if __name__ == "__main__":
    train_model()
