
import numpy as np
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, roc_auc_score, confusion_matrix
from sklearn.model_selection import cross_val_score
from datetime import datetime

class ModelEvaluator:
    
    def __init__(self):
        self.results = []
    
    def evaluate_model(self, model, X_test, y_test, model_name="Model", cv=None):
        print(f"\nتقييم {model_name}...")
        
        y_pred = model.predict(X_test)
        
        metrics = {
            'model_name': model_name,
            'accuracy': round(accuracy_score(y_test, y_pred), 4),
            'precision': round(precision_score(y_test, y_pred, average='weighted', zero_division=0), 4),
            'recall': round(recall_score(y_test, y_pred, average='weighted', zero_division=0), 4),
            'f1_score': round(f1_score(y_test, y_pred, average='weighted', zero_division=0), 4),
            'evaluation_date': datetime.now().isoformat(),
            'test_samples': len(y_test)
        }
        
        try:
            if hasattr(model, 'predict_proba') and len(set(y_test)) == 2:
                y_proba = model.predict_proba(X_test)[:, 1]
                metrics['roc_auc'] = round(roc_auc_score(y_test, y_proba), 4)
            else:
                metrics['roc_auc'] = 0.0
        except:
            metrics['roc_auc'] = 0.0
        
        cm = confusion_matrix(y_test, y_pred)
        metrics['confusion_matrix'] = cm.tolist()
        
        if cv:
            try:
                cv_scores = cross_val_score(model, X_test, y_test, cv=cv, scoring='f1_weighted')
                metrics['cv_mean'] = round(cv_scores.mean(), 4)
            except:
                pass
        
        self._print(metrics)
        self.results.append(metrics)
        return metrics
    
    def _print(self, m):
        print(f"   Accuracy:  {m['accuracy']:.2%}")
        print(f"   F1-Score:  {m['f1_score']:.2%}")
        print(f"   ROC-AUC:   {m.get('roc_auc', 0):.2%}")
    
    def select_best(self, results=None):
        results = results or self.results
        if not results:
            return None
        best = max(results, key=lambda x: x['f1_score'])
        print(f"\n أفضل نموذج: {best['model_name']} (F1: {best['f1_score']:.2%})")
        return best
    
    def compare_models(self, results=None):
        results = results or self.results
        if not results:
            return
        print("\n" + "=" * 70)
        print(f"{'النموذج':<18} {'Accuracy':<10} {'F1':<10} {'AUC':<10}")
        print("-" * 70)
        for r in results:
            print(f"{r['model_name']:<18} {r['accuracy']:<10} {r['f1_score']:<10} {r.get('roc_auc', 0):<10}")
        print("=" * 70)
