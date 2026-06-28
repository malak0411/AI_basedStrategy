from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List, Optional
from datetime import datetime, timedelta
import random
import json

from ...database import get_db
from ...models import OperationalTask, TaskProgressLog, TaskDependency, Employee
from ...schemas import DelayPredictionRequest, DelayPredictionResponse
from ...core.dependencies import get_current_user, has_role

router = APIRouter(prefix="/api/ai", tags=["الذكاء الاصطناعي"])

# ================================================================
# 1. التنبؤ بتأخر المهام (محاكاة)
# ================================================================

@router.post("/predict-delay", response_model=DelayPredictionResponse)
async def predict_delay(
    request: DelayPredictionRequest,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """
    محاكاة نموذج التنبؤ بتأخر المهام.
    في الإنتاج، سيتم استخدام نموذج ML حقيقي.
    """
    # جلب بيانات المهمة
    task = db.query(OperationalTask).filter(OperationalTask.task_id == request.task_id).first()
    if not task:
        raise HTTPException(status_code=404, detail="المهمة غير موجودة")
    
    # محاكاة التنبؤ (بديل للنموذج الحقيقي)
    # بناءً على التقدم الحالي والأيام المتبقية
    remaining = request.remaining_days
    progress = request.current_progress
    if remaining <= 0:
        probability_delay = 0.9 if progress < 100 else 0.1
    else:
        # إذا كان التقدم منخفضاً والأيام المتبقية قليلة -> احتمال تأخير عالٍ
        progress_rate = progress / max(1, 30 - remaining)  # متوسط التقدم يومياً
        days_needed = (100 - progress) / max(progress_rate, 0.1)
        probability_delay = min(1.0, days_needed / max(remaining, 1))
    
    # إضافة عشوائية بسيطة
    probability_delay = min(1.0, max(0.0, probability_delay + random.uniform(-0.1, 0.1)))
    
    confidence = 0.7 + (0.25 * min(1, len(request.history) / 10))
    
    if probability_delay < 0.3:
        prediction = "on_track"
        suggestion = "✅ المهمة تسير حسب الخطة، استمر بنفس الوتيرة"
    elif probability_delay < 0.7:
        prediction = "likely_delayed"
        suggestion = "⚠️ احتمال تأخير متوسط، يُفضل مراجعة الخطة وتكثيف العمل"
    else:
        prediction = "critical"
        suggestion = "🔴 خطر تأخير مرتفع! يرجى التدخل الفوري وإعادة توزيع الموارد"
    
    estimated_completion_days = max(1, (100 - progress) / max(progress_rate, 0.1))
    
    return DelayPredictionResponse(
        prediction=prediction,
        probability_delay=round(probability_delay, 2),
        confidence=round(confidence, 2),
        suggestion=suggestion,
        estimated_completion_days=round(estimated_completion_days, 1)
    )


# ================================================================
# 2. تفكيك المهام (محاكاة)
# ================================================================

@router.post("/decompose-task")
async def decompose_task(
    task_description: str,
    department_count: int = 3,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة"]))
):
    """
    محاكاة نموذج تفكيك المهام باستخدام الذكاء الاصطناعي.
    """
    # محاكاة القائمة الافتراضية للمهام الفرعية
    subtasks = [
        f"تحليل متطلبات: {task_description[:50]}...",
        "توزيع المهام على الفرق المعنية",
        "تنفيذ المهام الفرعية وفق الخطة الزمنية",
        "متابعة التنفيذ وتسجيل التقدم",
        "مراجعة النتائج وإعداد التقرير النهائي"
    ]
    # إضافة مهام إضافية حسب عدد الإدارات
    if department_count > 3:
        subtasks.append("التنسيق بين الإدارات المشاركة")
    if department_count > 4:
        subtasks.append("إعداد خطة الطوارئ")
    
    return {
        "parent_task": task_description,
        "subtasks": subtasks,
        "estimated_hours": len(subtasks) * 8,
        "suggested_departments": [f"الإدارة {i+1}" for i in range(min(department_count, 5))]
    }


# ================================================================
# 3. كشف الانحرافات المالية (محاكاة)
# ================================================================

@router.post("/detect-anomalies")
async def detect_anomalies(
    budget_id: int,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(has_role(["وزير / قيادة العليا", "وكيل وزارة"]))
):
    """
    محاكاة كشف الانحرافات المالية.
    """
    # محاكاة: إرجاع بعض الانحرافات العشوائية
    anomalies = []
    if random.random() > 0.5:
        anomalies.append({
            "type": "صرف غير طبيعي",
            "amount": round(random.uniform(10000, 50000), 2),
            "description": "تم رصد صرف أعلى من المتوسط بنسبة 35%",
            "severity": "متوسطة"
        })
    if random.random() > 0.7:
        anomalies.append({
            "type": "تجاوز الميزانية",
            "amount": round(random.uniform(50000, 200000), 2),
            "description": "تجاوز الميزانية المخصصة للمشروع",
            "severity": "عالية"
        })
    return {
        "budget_id": budget_id,
        "anomalies": anomalies,
        "total_anomalies": len(anomalies),
        "status": "مراجعة" if anomalies else "سليم"
    }


# ================================================================
# 4. توصيات ذكية للمهام المتأخرة
# ================================================================

@router.get("/recommendations")
async def get_recommendations(
    task_id: Optional[int] = None,
    db: Session = Depends(get_db),
    current_user: Employee = Depends(get_current_user)
):
    """
    جلب توصيات ذكية للمهام المتأخرة أو المعرضة للتأخير.
    """
    # جلب المهام المتأخرة أو التي قد تتأخر
    today = datetime.now().date()
    delayed_tasks = db.query(OperationalTask).filter(
        OperationalTask.end_date < today,
        OperationalTask.status_id != db.query(DictStatus).filter(DictStatus.code == "completed").first().status_id,
        OperationalTask.is_active == True
    ).limit(5).all()
    
    recommendations = []
    for task in delayed_tasks:
        # محاكاة توصيات
        rec = {
            "task_id": task.task_id,
            "task_title": task.title,
            "recommendation": f"تأخرت المهمة {task.title} عن موعدها بـ {(today - task.end_date).days} يوماً. يُنصح بإعادة توزيع الموارد أو تمديد الجدول الزمني.",
            "priority": "مرتفع" if (today - task.end_date).days > 10 else "متوسط",
            "status": "مقترح"
        }
        recommendations.append(rec)
    
    # إذا لم تكن هناك مهام متأخرة، نقدم توصيات عامة
    if not recommendations:
        recommendations.append({
            "task_id": None,
            "task_title": "جميع المهام",
            "recommendation": "✅ لا توجد مهام متأخرة حالياً. استمر في مراقبة التقدم لضمان تحقيق الأهداف.",
            "priority": "منخفض",
            "status": "مطمئن"
        })
    
    return recommendations

 
