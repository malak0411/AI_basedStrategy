
import os
import json
from typing import Optional, Dict, Any
from dotenv import load_dotenv

# تحميل المتغيرات من .env
load_dotenv()

# استيراد مكتبة Google
try:
    import google.generativeai as genai
    GEMINI_AVAILABLE = True
except ImportError:
    GEMINI_AVAILABLE = False
    print("⚠️ google-generativeai غير مثبت. استخدم: pip install google-generativeai")


class GeminiClient:
    
    # أسماء النماذج المتاحة
    MODEL_PRO = "gemini-2.5-pro-exp-03-25"    # Gemini 2.5 Pro (تجريبي)
    MODEL_FLASH = "gemini-2.5-flash"           # Gemini 2.5 Flash (سريع)
    MODEL_FALLBACK = "gemini-1.5-flash"        # احتياطي إذا لم يتوفر 2.5
    
    def __init__(self):
        if not GEMINI_AVAILABLE:
            raise ImportError("مكتبة google-generativeai غير مثبتة")
        
        # جلب المفتاح من البيئة
        self.api_key = os.getenv("GEMINI_API_KEY")
        
        if not self.api_key:
            raise ValueError(
                "❌ GEMINI_API_KEY غير موجود في ملف .env\n"
                "أضف: GEMINI_API_KEY=your_key_here في ملف .env"
            )
        
        # تهيئة المكتبة
        genai.configure(api_key=self.api_key)
        
        # إعدادات التوليد الافتراضية
        self.default_config = {
            "temperature": 0.3,        # إبداع منخفض (نتائج متسقة)
            "top_p": 0.95,
            "top_k": 40,
            "max_output_tokens": 8192, # حد أقصى للرد
        }
        
        # إعدادات الأمان (مهم للمشاريع الحكومية)
        self.safety_settings = [
            {"category": "HARM_CATEGORY_HARASSMENT", "threshold": "BLOCK_ONLY_HIGH"},
            {"category": "HARM_CATEGORY_HATE_SPEECH", "threshold": "BLOCK_ONLY_HIGH"},
            {"category": "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold": "BLOCK_ONLY_HIGH"},
            {"category": "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold": "BLOCK_ONLY_HIGH"},
        ]
        
        # تهيئة النماذج (ستُنشأ عند الحاجة)
        self._pro_model = None
        self._flash_model = None
        
        print(f"✅ Gemini Client جاهز (Pro: {self.MODEL_PRO})")
    
    def _get_model(self, model_type: str = "pro"):
        """الحصول على النموذج المناسب (مع تخزين مؤقت)"""
        if model_type == "flash":
            if self._flash_model is None:
                self._flash_model = genai.GenerativeModel(
                    model_name=self.MODEL_FLASH,
                    generation_config=self.default_config,
                    safety_settings=self.safety_settings
                )
            return self._flash_model
        else:
            if self._pro_model is None:
                try:
                    self._pro_model = genai.GenerativeModel(
                        model_name=self.MODEL_PRO,
                        generation_config=self.default_config,
                        safety_settings=self.safety_settings
                    )
                except Exception:
                    # استخدام النموذج الاحتياطي
                    print(f"⚠️ {self.MODEL_PRO} غير متوفر، استخدام {self.MODEL_FALLBACK}")
                    self._pro_model = genai.GenerativeModel(
                        model_name=self.MODEL_FALLBACK,
                        generation_config=self.default_config,
                        safety_settings=self.safety_settings
                    )
            return self._pro_model
    
    def generate(
        self,
        prompt: str,
        system_instruction: Optional[str] = None,
        model_type: str = "pro",
        temperature: Optional[float] = None,
        max_tokens: Optional[int] = None
    ) -> str:
        """
        استدعاء Gemini API
        
        Args:
            prompt: النص الرئيسي المرسل للنموذج
            system_instruction: تعليمات النظام (تحدد سلوك النموذج)
            model_type: "pro" للتخطيط الاستراتيجي، "flash" للتشغيل السريع
            temperature: درجة الإبداع (0.0-1.0)، أقل = أكثر دقة
            max_tokens: الحد الأقصى للرد
        
        Returns:
            str: النص المُولد من Gemini
        
        Raises:
            Exception: إذا فشل الاستدعاء بعد 3 محاولات
        """
        # إعدادات مخصصة إذا تم توفيرها
        generation_config = self.default_config.copy()
        if temperature is not None:
            generation_config["temperature"] = temperature
        if max_tokens is not None:
            generation_config["max_output_tokens"] = max_tokens
        
        # الحصول على النموذج
        model = self._get_model(model_type)
        
        # بناء المحتوى الكامل
        if system_instruction:
            full_prompt = f"[تعليمات النظام]\n{system_instruction}\n\n[المهمة]\n{prompt}"
        else:
            full_prompt = prompt
        
        # محاولة الاستدعاء (مع إعادة المحاولة)
        max_retries = 3
        for attempt in range(max_retries):
            try:
                response = model.generate_content(full_prompt)
                
                # التحقق من وجود رد
                if response and response.text:
                    return response.text
                else:
                    raise ValueError("استجابة فارغة من Gemini")
                    
            except Exception as e:
                error_msg = str(e)
                
                # إذا كان خطأ في الحظر (سلامة)
                if "SAFETY" in error_msg.upper() or "BLOCK" in error_msg.upper():
                    print(f"⚠️ تم حظر المحتوى لأسباب السلامة (محاولة {attempt + 1})")
                    # تخفيف التعليمات وإعادة المحاولة
                    full_prompt = prompt + "\n\n(ملاحظة: يرجى تقديم رد مهني ومناسب للاستخدام الحكومي)"
                else:
                    print(f"⚠️ خطأ في استدعاء Gemini (محاولة {attempt + 1}): {error_msg[:100]}")
                
                if attempt == max_retries - 1:
                    raise Exception(f"فشل استدعاء Gemini بعد {max_retries} محاولات: {error_msg}")
    
    def generate_json(
        self,
        prompt: str,
        system_instruction: Optional[str] = None,
        model_type: str = "pro"
    ) -> Dict[str, Any]:
        """
        استدعاء Gemini وإرجاع النتيجة كـ JSON
        
        Args:
            نفس generate() ولكن يُضيف تعليمات لإرجاع JSON
        
        Returns:
            dict: JSON مُعالج
        
        Raises:
            ValueError: إذا لم يكن الرد JSON صالح
        """
        # إضافة تعليمات JSON
        json_instruction = (
            "يجب أن يكون الرد بصيغة JSON صالحة فقط، بدون أي نص آخر. "
            "لا تستخدم علامات ```json```. أعد JSON مباشرة."
        )
        
        if system_instruction:
            system_instruction = f"{system_instruction}\n\n{json_instruction}"
        else:
            system_instruction = json_instruction
        
        # استدعاء النموذج
        response_text = self.generate(
            prompt=prompt,
            system_instruction=system_instruction,
            model_type=model_type,
            temperature=0.2  # أقل إبداع لـ JSON دقيق
        )
        
        # محاولة تحليل JSON
        try:
            # تنظيف النص (إزالة أي نص قبل/بعد JSON)
            response_text = response_text.strip()
            
            # إزالة علامات markdown إذا وجدت
            if response_text.startswith("```json"):
                response_text = response_text[7:]
            if response_text.startswith("```"):
                response_text = response_text[3:]
            if response_text.endswith("```"):
                response_text = response_text[:-3]
            response_text = response_text.strip()
            
            return json.loads(response_text)
            
        except json.JSONDecodeError as e:
            raise ValueError(
                f"فشل تحليل استجابة Gemini كـ JSON: {str(e)}\n"
                f"الاستجابة: {response_text[:200]}..."
            )


# نسخة عامة للاستخدام في باقي أجزاء النظام
gemini_client = GeminiClient()
