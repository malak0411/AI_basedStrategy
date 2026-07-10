"""
سكريبت تدريب نموذج التوصيات الذكية
"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..'))

from app.ai.models.recommender import Recommender

def main():
    print("\n" + "🏛️" * 30)
    print("   نظام إدارة الاستراتيجية - تدريب نموذج التوصيات")
    print("🏛️" * 30)
    
    try:
        rec = Recommender()
        metrics = rec.train(force=True)
        
        if metrics:
            print("\n" + "=" * 60)
            print("✅ اكتمل التدريب بنجاح!")
            print("=" * 60)
            print(f"   النموذج: {metrics['model_name']}")
            print(f"   F1-Score: {metrics['f1_score']:.2%}")
            print(f"   Accuracy: {metrics['accuracy']:.2%}")
            print(f"   عدد الفئات: {metrics.get('num_classes', 'N/A')}")
            
            # اختبار سريع
            print("\n🧪 اختبار توصية لمهمة...")
            result = rec.recommend(task_id=1)
            if result and 'recommendations' in result:
                for r in result['recommendations']:
                    print(f"   • {r['action_ar']} (ثقة: {r['confidence']:.0%})")
                    print(f"     {r['reason']}")
        else:
            print("\n❌ فشل التدريب - تحقق من البيانات")
            
    except Exception as e:
        print(f"\n❌ خطأ غير متوقع: {str(e)}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()
