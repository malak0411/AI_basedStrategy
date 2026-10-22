import sys
import os


sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', '..', '..'))

from app.ai.models.delay_predictor import DelayPredictor

def main():

    print("\n" + "🏛️" * 30)
    print("   نظام إدارة الاستراتيجية - تدريب نموذج التنبؤ بالتأخير")
    print("🏛️" * 30)
    
    try:

        predictor = DelayPredictor()
        metrics = predictor.train(force=True)
        
        if metrics:
            print("\n" + "=" * 60)
            print(" اكتمل التدريب بنجاح!")
            print("=" * 60)
            print(f"   النموذج: {metrics['model_name']}")
            print(f"   F1-Score: {metrics['f1_score']:.2%}")
            print(f"   Accuracy: {metrics['accuracy']:.2%}")
            print(f"   ROC-AUC: {metrics.get('roc_auc', 0):.2%}")
            print(f"   عينات الاختبار: {metrics['test_samples']}")
        else:
            print(" فشل التدريب - البيانات غير كافية")
            
    except Exception as e:
        print(f" خطأ غير متوقع: {str(e)}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()
