import mysql.connector
import numpy as np
import os
import pickle
from sklearn.tree import DecisionTreeClassifier
from sklearn.preprocessing import LabelEncoder

DB = {"host": "localhost", "user": "root", "password": "", "database": "edupathdb"}

TOTAL_QUESTIONS = 60


def get_data():
    conn = mysql.connector.connect(**DB)
    cursor = conn.cursor(dictionary=True)

    # Gather labeled data per student_assessment_id joining final_result and majors, and pull part scores.
    cursor.execute(
        f"""
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
    )

    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


def prepare_features(ans_list):
    # Prepare features from part totals: expects a tuple/dict with part1_total/part2_total/part3_total
    # If called with a list, interpret as [p1,p2,p3]
    if isinstance(ans_list, (list, tuple)) and len(ans_list) == 3:
        return [int(ans_list[0] or 0), int(ans_list[1] or 0), int(ans_list[2] or 0)]
    # Otherwise, assume dict-like with keys
    p1 = int(ans_list.get("part1_total") or 0)
    p2 = int(ans_list.get("part2_total") or 0)
    p3 = int(ans_list.get("part3_total") or 0)
    return [p1, p2, p3]


def train_model():
    data = get_data()

    if len(data) < 3:
        print("NOT ENOUGH TRAINING DATA")
        return

    X, y = [], []

    for row in data:
        p1 = row.get("part1_total")
        p2 = row.get("part2_total")
        p3 = row.get("part3_total")
        if p1 is None or p2 is None or p3 is None:
            continue
            X.append(prepare_features([p1, p2, p3]))
            y.append(int(row.get("major_id")))

    encoder = LabelEncoder()
    y_enc = encoder.fit_transform(y)

    model = DecisionTreeClassifier(random_state=42)
    model.fit(X, y_enc)

    model_path = os.path.join(os.path.dirname(__file__), "model.pkl")
    with open(model_path, "wb") as f:
        pickle.dump({"model": model, "encoder": encoder}, f)

    print(f"MODEL TRAINED SUCCESSFULLY! Saved to: {model_path}")


if __name__ == "__main__":
    train_model()
