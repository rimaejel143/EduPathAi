import mysql.connector

cfg = {"host": "localhost", "user": "root", "password": "", "database": "edupathdb"}
conn = mysql.connector.connect(**cfg)
cur = conn.cursor()
cur.execute(
    "SELECT student_assessment_id,user_id FROM student_assessment ORDER BY student_assessment_id DESC LIMIT 10"
)
rows = cur.fetchall()
print("student_assessment rows:")
for r in rows:
    print(r)
print("\nfinal_result rows latest 10:")
cur.execute(
    "SELECT student_assessment_id, major_id, feedback FROM final_result ORDER BY student_assessment_id DESC LIMIT 10"
)
for r in cur.fetchall():
    print(r)

print("\nmajors table (id -> name):")
cur.execute("SELECT major_id, major_name FROM majors ORDER BY major_id ASC")
for r in cur.fetchall():
    print(r)
print("\nassessment_part_results latest 30:")
cur.execute(
    "SELECT student_assessment_id, part_number, total_score FROM assessment_part_results ORDER BY student_assessment_id DESC, part_number ASC LIMIT 30"
)
for r in cur.fetchall():
    print(r)
cur.close()
conn.close()
