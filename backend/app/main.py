from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

# ============================================================
# استيراد الـ Routers
# ============================================================
from app.api.routers import auth, strategic, tasks, dashboard, employees, budget, risks, kpis
from app.api.routers import system_config, admin, location, departments as dept_routes
from app.ai.routers.ai_router import router as ai_router

# ============================================================
# إنشاء التطبيق
# ============================================================
app = FastAPI(
    title="AI Strategy Management System",
    description="نظام إدارة الاستراتيجية الحكومي المعتمد على الذكاء الاصطناعي - وزارة النفط والمعادن",
    version="2.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# ============================================================
# إعداد CORS
# ============================================================
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ============================================================
# تسجيل الـ Routers
# ============================================================

# المصادقة
app.include_router(auth.router)

# التخطيط الاستراتيجي
app.include_router(strategic.router)

# المهام
app.include_router(tasks.router)

# لوحات التحكم
app.include_router(dashboard.router)

# الموظفين
app.include_router(employees.router)

# الميزانية
app.include_router(budget.router)

# المخاطر
app.include_router(risks.router)

# مؤشرات الأداء
app.include_router(kpis.router)

# إعدادات النظام
app.include_router(system_config.router)

# إدارة النظام
app.include_router(admin.router)

# تتبع المواقع
app.include_router(location.router)

# الإدارات
app.include_router(dept_routes.router)

# ============================================================
# الذكاء الاصطناعي (AI Router)
# ============================================================
app.include_router(ai_router)

# ============================================================
# نقطة البداية
# ============================================================
@app.get("/")
async def root():
    return {
        "message": "AI Strategy Management System API",
        "version": "2.0.0",
        "status": "running",
        "docs": "/docs"
    }

@app.get("/health")
async def health_check():
    return {"status": "healthy"}

# ============================================================
# بدء جدولة AI عند تشغيل الخادم
# ============================================================
@app.on_event("startup")
async def startup_event():
    """بدء الجدولة التلقائية عند تشغيل الخادم"""
    from app.ai.services.scheduler import ai_scheduler
    ai_scheduler.start()
    print("🚀 تم بدء جدولة AI التلقائية")
