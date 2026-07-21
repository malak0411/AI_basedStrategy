"""
Gemini API Client - مجاني مع نظام انتظار ذكي
"""
import os
import time
import google.generativeai as genai
from dotenv import load_dotenv

load_dotenv()

class GeminiClient:
    
    MODEL_FLASH = "gemini-2.0-flash"
    
    def __init__(self):
        self.api_key = os.getenv("GEMINI_API_KEY")
        if not self.api_key:
            raise ValueError("GEMINI_API_KEY غير موجود في .env")
        
        genai.configure(api_key=self.api_key)
        
        self.safety_settings = [
            {"category": "HARM_CATEGORY_HARASSMENT", "threshold": "BLOCK_ONLY_HIGH"},
            {"category": "HARM_CATEGORY_HATE_SPEECH", "threshold": "BLOCK_ONLY_HIGH"},
            {"category": "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold": "BLOCK_ONLY_HIGH"},
            {"category": "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold": "BLOCK_ONLY_HIGH"},
        ]
        
        self.model = genai.GenerativeModel(
            model_name=self.MODEL_FLASH,
            safety_settings=self.safety_settings
        )
        
        print(f"✅ Gemini Client جاهز (مجاني: {self.MODEL_FLASH})")

    def generate(self, prompt: str, system_instruction: str = None) -> str:
        """استدعاء Gemini مع انتظار ذكي عند تجاوز الحصة"""
        
        full_prompt = prompt
        if system_instruction:
            full_prompt = f"{system_instruction}\n\n{prompt}"
        
        max_retries = 5
        for attempt in range(max_retries):
            try:
                response = self.model.generate_content(full_prompt)
                if response and response.text:
                    return response.text
                    
            except Exception as e:
                error_msg = str(e)
                
                # تجاوز الحصة - انتظر
                if "429" in error_msg or "quota" in error_msg.lower() or "ResourceExhausted" in error_msg:
                    # استخراج وقت الانتظار من رسالة الخطأ
                    wait_time = 15  # افتراضي
                    if "retry_delay" in error_msg:
                        try:
                            import re
                            match = re.search(r'seconds:\s*(\d+)', error_msg)
                            if match:
                                wait_time = int(match.group(1)) + 2
                        except:
                            pass
                    
                    print(f"⏳ تجاوز الحصة - انتظار {wait_time} ثانية (محاولة {attempt + 1}/{max_retries})...")
                    time.sleep(wait_time)
                    continue
                
                # خطأ آخر
                print(f"⚠️ خطأ (محاولة {attempt + 1}): {error_msg[:100]}")
                time.sleep(5)
        
        raise Exception(f"فشل بعد {max_retries} محاولات")

gemini_client = GeminiClient()
