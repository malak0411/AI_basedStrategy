
import pandas as pd
import numpy as np
from sklearn.preprocessing import StandardScaler, OneHotEncoder
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
import joblib
import os

class FeatureEngineer:
    
    def __init__(self):
        self.numerical_features = []
        self.categorical_features = []
        self.preprocessor = None
        self.is_fitted = False
    
    def prepare_data(self, df, target_col='is_delayed', test_size=0.2, random_state=42):
        print(" بدء معالجة الميزات...")
        
        feature_cols = [c for c in df.columns if c not in [target_col, 'task_id']]
        self._identify_features(df, feature_cols)
        
        X = df[feature_cols]
        y = df[target_col].astype(int)
        

        y_counts = pd.Series(y).value_counts()
        if y_counts.min() >= 2:
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=test_size, random_state=random_state, stratify=y
            )
        else:
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=test_size, random_state=random_state
            )
        
        print(f" تدريب: {len(X_train)} | اختبار: {len(X_test)} | ميزات: {len(feature_cols)}")
        
        self._build_preprocessor()
        X_train_p = self.preprocessor.fit_transform(X_train)
        X_test_p = self.preprocessor.transform(X_test)
        self.is_fitted = True
        
        print("اكتملت معالجة الميزات")
        return X_train_p, X_test_p, y_train, y_test
    
    def transform_single(self, features_dict):
        if not self.is_fitted:
            raise ValueError("FeatureEngineer غير مدرب")
        df = pd.DataFrame([features_dict])
        feature_cols = [c for c in df.columns if c not in ['task_id', 'is_delayed']]
        return self.preprocessor.transform(df[feature_cols])
    
    def _identify_features(self, df, feature_cols):
        categorical_candidates = ['priority_id', 'status_id', 'department_id', 'major_task_id', 'is_cross_functional', 'is_overdue']
        self.numerical_features = [c for c in feature_cols if c not in categorical_candidates]
        self.categorical_features = [c for c in feature_cols if c in categorical_candidates]
        print(f"   عددية: {len(self.numerical_features)} | فئوية: {len(self.categorical_features)}")
    
    def _build_preprocessor(self):
        num_pipe = Pipeline([('scaler', StandardScaler())])
        cat_pipe = Pipeline([('onehot', OneHotEncoder(handle_unknown='ignore', sparse_output=False))])
        self.preprocessor = ColumnTransformer([
            ('num', num_pipe, self.numerical_features),
            ('cat', cat_pipe, self.categorical_features)
        ], remainder='drop')
    
    def save(self, filepath):
        os.makedirs(os.path.dirname(filepath), exist_ok=True)
        joblib.dump({
            'preprocessor': self.preprocessor,
            'numerical': self.numerical_features,
            'categorical': self.categorical_features,
            'fitted': self.is_fitted
        }, filepath)
        print(f" تم الحفظ: {filepath}")
    
    def load(self, filepath):
        if os.path.exists(filepath):
            data = joblib.load(filepath)
            self.preprocessor = data['preprocessor']
            self.numerical_features = data['numerical']
            self.categorical_features = data['categorical']
            self.is_fitted = data['fitted']
            return True
        return False
