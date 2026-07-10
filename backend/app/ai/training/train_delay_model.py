"""
سكريبت تدريب نموذج التنبؤ بالتأخير
يستخدم بيانات المهام من قاعدة البيانات
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..'))

from app.database import SessionLocal
from app.models import OperationalTask
from app.ai.models.delay_predictor import DelayPredictor
from datetime import date

def get_training_data():
    """جلب بيانات التدريب من قاعدة البيانات"""
    db = SessionLocal()
    try:
        tasks = db.query(OperationalTask).all()
        
        tasks_data = []
        labels = []
        
        for task in tasks:
            task_dict = {
                'progress': 0,
                'end_date': task.end_date,
                'start_date': task.start_date,
                'priority_id': task.priority_id or 2,
                'estimated_hours': float(task.estimated_hours or 0),
                'actual_hours': float(task.actual_hours or 0),
                'is_cross_functional': task.is_cross_functional or False,
            }
            
            tasks_data.append(task_dict)
            
            # تحديد التصنيف: هل المهمة متأخرة؟
            is_delayed = task.status_id == 3  # delayed
            labels.append(1 if is_delayed else 0)
        
        return tasks_data, labels
    
    finally:
        db.close()

def main():
    print("=" * 50)
    print("🤖 تدريب نموذج التنبؤ بتأخير المهام")
    print("=" * 50)
    
    # جلب البيانات
    print("\n📊 جلب بيانات التدريب من قاعدة البيانات...")
    tasks_data, labels = get_training_data()
    
    print(f"📋 عدد المهام: {len(tasks_data)}")
    print(f"⚠️ المهام المتأخرة: {sum(labels)}")
    print(f"✅ المهام غير المتأخرة: {len(labels) - sum(labels)}")
    
    # تدريب النموذج
    print("\n🟡 بدء التدريب...")
    predictor = DelayPredictor()
    success = predictor.train(tasks_data, labels)
    
    if success:
        print("\n🎉 تم تدريب النموذج وحفظه بنجاح!")
        
        # اختبار سريع
        print("\n🧪 اختبار النموذج...")
        test_task = {
            'progress': 30,
            'end_date': date(2026, 7, 10),
            'start_date': date(2026, 6, 1),
            'priority_id': 2,
            'estimated_hours': 40,
            'actual_hours': 10,
            'is_cross_functional': False,
        }
        
        result = predictor.predict(test_task)
        print(f"📊 نتيجة الاختبار:")
        print(f"   احتمال التأخير: {result['delay_probability']}%")
        print(f"   مستوى الخطر: {result['risk_label']}")
        print(f"   التوصية: {result['recommendation']}")
    else:
        print("\n⚠️ فشل التدريب - بيانات غير كافية")

if __name__ == "__main__":
    main()
