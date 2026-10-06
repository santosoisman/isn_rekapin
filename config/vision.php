<?php

/*
 * Scan struk pakai AI (model vision) lewat endpoint kompatibel OpenAI:
 * 9router, OpenRouter, Gemini (endpoint OpenAI-compatible), dll.
 * Kalau VISION_AI_BASE_URL / VISION_AI_MODEL kosong, scan pakai OCR.space seperti biasa.
 * Kalau AI gagal/timeout, otomatis jatuh ke OCR.space.
 */
return [
    // contoh 9router: http://127.0.0.1:20128/v1
    'base_url' => env('VISION_AI_BASE_URL'),
    'api_key' => env('VISION_AI_API_KEY'),
    // nama model/combo yang mendukung gambar (vision)
    'model' => env('VISION_AI_MODEL'),
    'timeout' => (int) env('VISION_AI_TIMEOUT', 30),
];
