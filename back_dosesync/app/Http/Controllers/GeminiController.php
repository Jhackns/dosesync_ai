<?php

namespace App\Http\Controllers;

use App\Services\GeminiAIService;
use Illuminate\Http\Request;

class GeminiController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiAIService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function checkModels()
    {
        return $this->geminiService->listModels();
    }
}