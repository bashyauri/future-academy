# AI Integration Learning Guide for Future Academy

**Date**: February 15, 2026  
**Project**: Future Academy LMS  
**Focus**: AI Integration Skills & Implementation

---

## Table of Contents

1. [Overview](#overview)
2. [Foundation Concepts](#foundation-concepts)
3. [Official Resources](#official-resources)
4. [Learning Platforms](#learning-platforms)
5. [Hands-On Projects](#hands-on-projects)
6. [PHP/Laravel Specific](#phplaravel-specific)
7. [Key Concepts to Master](#key-concepts-to-master)
8. [Implementation Roadmap](#implementation-roadmap)
9. [AI Use Cases for Future Academy](#ai-use-cases-for-future-academy)
10. [Communities & Support](#communities--support)
11. [Quick Reference](#quick-reference)

---

## Overview

AI integration is becoming essential for modern educational platforms. This guide covers everything needed to add intelligent features to Future Academy.

### Why AI for Future Academy?

- 📊 **Personalized Learning Paths** - AI recommends content based on student performance
- 🤖 **Intelligent Tutoring** - AI explains concepts and answers student questions
- 📝 **Quiz Generation** - AI creates custom questions from study materials
- 📈 **Performance Analysis** - Identify student weak areas automatically
- 💬 **Smart Chat Support** - AI-powered student support system

---

## Foundation Concepts

### What You Need to Know First

#### 1. **Large Language Models (LLMs)**
- Computer programs trained on massive text datasets
- Can understand and generate human-like text
- Examples: GPT-4, Claude, Gemini, Mistral
- Cost model: Pay per token (words/subwords)

#### 2. **Tokens**
- Smallest unit of text an AI processes
- ~4 characters ≈ 1 token
- 1,000 tokens ≈ 750 words
- Affects both speed and cost

#### 3. **Prompts**
- Instructions you give to AI
- Quality of prompt = Quality of output
- Can be as simple as: "Analyze this quiz"
- Or complex with multiple steps and examples

#### 4. **Temperature** (Randomness)
```
0.0 = Deterministic (always same answer)
0.7 = Balanced (default)
1.0 = Creative (random variations)

Use 0.0 for quiz grading
Use 0.7 for tutoring
Use 1.0 for brainstorming
```

#### 5. **Context Window** (Memory)
- How much text AI can remember at once
- GPT-4: 128K tokens (~96,000 words!)
- Older models: 4K tokens
- Affects what it can analyze

---

## Official Resources

### MCP (Model Context Protocol)

> The protocol for connecting AI tools to applications

| Resource | Link | Time |
|----------|------|------|
| **Official Specification** | https://spec.modelcontextprotocol.io | 30 min |
| **GitHub Repository** | https://github.com/modelcontextprotocol/specification | - |
| **Reference Implementation** | https://github.com/modelcontextprotocol/sdk-js | Tutorial |
| **Python SDK** | https://github.com/modelcontextprotocol/sdk-python | Tutorial |

**What You'll Learn:**
- How AI tools request information from your app
- How to safely expose your data
- Real-time AI-app communication

### OpenAI

| Resource | Link | Cost |
|----------|------|------|
| **API Documentation** | https://platform.openai.com/docs | Free |
| **Playground** | https://platform.openai.com/playground | Free tier |
| **Cookbook** | https://github.com/openai/openai-cookbook | Free |
| **Models Overview** | https://platform.openai.com/docs/models | - |

**Best Models for Education:**
- `gpt-4-turbo` - Most capable, $0.03 per 1K input tokens
- `gpt-3.5-turbo` - Fast & cheap, $0.0005 per 1K input tokens
- `text-embedding-3-small` - For semantic search, $0.02 per 1M tokens

### Anthropic (Claude)

| Resource | Link | Strengths |
|----------|------|-----------|
| **API Docs** | https://docs.anthropic.com | Educational content |
| **Claude 3 Models** | https://www.anthropic.com/claude | Very helpful explanations |
| **SDKs** | https://github.com/anthropics | Well-documented |

**Why Claude for Education:**
- Excellent at explaining concepts
- Good at following detailed instructions
- Better at reasoning through problems

### Google Gemini

| Resource | Link | Specialty |
|----------|------|-----------|
| **AI Studio** | https://ai.google.dev | Quick prototyping |
| **API Docs** | https://ai.google.dev/docs | Free tier |
| **Cookbook** | https://github.com/google-gemini/cookbook | Examples |

### Mistral

| Resource | Link | Advantage |
|----------|------|-----------|
| **Platform** | https://console.mistral.ai | Open-source friendly |
| **Documentation** | https://docs.mistral.ai | Easy to understand |
| **Models** | https://docs.mistral.ai/capabilities/function_calling | Good function calling |

---

## Learning Platforms

### Top Course Recommendations

#### 1. **DeepLearning.AI** ⭐ RECOMMENDED
**Website**: https://deeplearning.ai

| Course | Duration | Level | Cost |
|--------|----------|-------|------|
| Prompt Engineering for Developers | 1 hour | Beginner | Free |
| LangChain for LLM Application Dev | 2 hours | Intermediate | Free |
| Building Systems with Claude | 1 hour | All Levels | Free |
| Short Courses Directory | Varies | All | Free |

**Why**: Short, practical, instructor-led courses. Perfect for busy professionals.

#### 2. **Hugging Face Course** ⭐ RECOMMENDED
**Website**: https://huggingface.co/course

| Topic | Time | Level |
|-------|------|-------|
| LLMs Fundamentals | 2-3 hours | Beginner |
| Transformers Library | 4-5 hours | Intermediate |
| Fine-tuning Models | 3-4 hours | Advanced |

**Why**: Free, comprehensive, includes hands-on coding.

#### 3. **Andrew Ng's Coursera**
**Website**: https://www.coursera.org/instructor/andrewng

- AI for Everyone (5 hours) - High level overview
- Machine Learning Specialization (3 months) - Deep dive
- Deep Learning Specialization (3 months) - Advanced

**Cost**: Free to audit, ~$40/month for certificate

#### 4. **Fast.ai**
**Website**: https://www.fast.ai

- Practical Deep Learning for Coders (14 weeks)
- Computational Linear Algebra (10 weeks)

**Philosophy**: Top-down learning (application first, theory second)

---

## Hands-On Projects

### Project 1: Build a Chatbot (Beginner - 4 hours)

**Goal**: Create an AI that answers questions about your quiz content

```php
<?php
// Step 1: Install OpenAI
composer require openai-php/laravel

// Step 2: Create a service
class QuizChatbot {
    public function answerQuestion($quizId, $question) {
        $quiz = Quiz::with('questions')->find($quizId);
        
        $response = \OpenAI\Client::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful tutor explaining quiz concepts.'
                ],
                [
                    'role' => 'user',
                    'content' => "In this quiz: {$quiz->title}, {$question}"
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);
        
        return $response['choices'][0]['message']['content'];
    }
}

// Step 3: Use in Livewire component
$answer = app(QuizChatbot::class)->answerQuestion(1, "What's the answer to question 3?");
```

**Resources**:
- OpenAI PHP SDK: https://github.com/openai-php/laravel
- Tutorial: https://laravel.com/docs/helpers#openai

### Project 2: AI Quiz Analysis (Intermediate - 8 hours)

**Goal**: Analyze student performance and identify weak areas

```php
<?php
class QuizAnalyzer {
    public function analyzePerformance($studentId) {
        $results = QuizResult::where('user_id', $studentId)
            ->with('answers')
            ->get();
        
        $analysis = \OpenAI\Client::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Analyze quiz performance and identify learning gaps'
                ],
                [
                    'role' => 'user',
                    'content' => "Analyze: " . json_encode([
                        'total_attempts' => $results->count(),
                        'average_score' => $results->avg('score'),
                        'wrong_questions' => $results->pluck('wrong_answer_ids'),
                        'time_spent' => $results->sum('time_spent')
                    ])
                ]
            ],
            'temperature' => 0.3 // Low temperature for analysis
        ]);
        
        return [
            'analysis' => $analysis['choices'][0]['message']['content'],
            'recommendations' => $this->generateRecommendations($results),
            'next_topics' => $this->suggestNextTopics($results)
        ];
    }
}
```

**Skills Developed**:
- Prompt engineering for analysis
- Structured data analysis with AI
- Integration with existing database

### Project 3: Question Generation (Advanced - 12 hours)

**Goal**: Generate quiz questions from study material

```php
<?php
class QuestionGenerator {
    public function generateQuestions($contentId, $count = 5) {
        $content = StudyMaterial::with('videos', 'articles')->find($contentId);
        
        $prompt = "Generate {$count} multiple choice questions about: {$content->title}\n";
        $prompt .= "Content summary: {$content->summary}\n";
        $prompt .= "Format: JSON array with keys: question, options (array of 4), correct_answer (0-3), explanation";
        
        $response = \OpenAI\Client::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Generate educational multiple choice questions'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.8 // Moderate randomness for variety
        ]);
        
        $questions = json_decode($response['choices'][0]['message']['content']);
        
        // Save to database
        foreach ($questions as $q) {
            Question::create([
                'quiz_id' => $contentId,
                'text' => $q->question,
                'type' => 'multiple_choice',
                'options' => $q->options,
                'correct_answer' => $q->correct_answer,
                'explanation' => $q->explanation,
                'generated_by_ai' => true
            ]);
        }
        
        return $questions;
    }
}
```

### Project 4: AI Tutoring System (Advanced - 20 hours)

**Goal**: Interactive AI tutor that guides student learning

```php
<?php
class AITutor {
    public function tutorStudent($studentId, $questionId) {
        $student = User::find($studentId);
        $question = Question::find($questionId);
        $progressHistory = StudentProgress::where('user_id', $studentId)->get();
        
        $systemPrompt = "You are an expert tutor. Guide the student to discover the answer themselves.";
        $systemPrompt .= "\nStudent learning level: " . $student->level;
        $systemPrompt .= "\nPrevious topics mastered: " . json_encode($progressHistory->pluck('topic'));
        
        $response = \OpenAI\Client::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Help me understand: {$question->text}"]
            ],
            'temperature' => 0.6
        ]);
        
        return [
            'hint' => $response['choices'][0]['message']['content'],
            'step' => 'initial_guidance',
            'continue' => true
        ];
    }
}
```

---

## PHP/Laravel Specific

### Official Packages

#### 1. **Laravel AI** (Official) ⭐
```bash
composer require laravel/ai
```

**Features**:
- Built-in OpenAI, Anthropic, Mistral support
- Fluent interface
- Streaming support
- Cost tracking

**Example**:
```php
use Illuminate\Support\Facades\AI;

// Simple usage
$response = AI::chat()
    ->asString()
    ->system('You are a helpful tutor')
    ->prompt('Explain photosynthesis')
    ->send();

// Structured output
$data = AI::chat()
    ->asJson()
    ->system('Return JSON')
    ->prompt('Analyze these results')
    ->send();
```

**Link**: https://laravel.com/docs/ai

#### 2. **Spatie AI Tools**
```bash
composer require spatie/laravel-ai
```

**Features**:
- Helper functions
- Cache integration
- Streaming
- Testing utilities

**Link**: https://github.com/spatie/laravel-ai

#### 3. **PHP OpenAI Client**
```bash
composer require openai-php/client
```

**Features**:
- Direct API access
- Full feature coverage
- Type hints
- SDKs for other languages

**Link**: https://github.com/openai-php/client

### Integration with Livewire

```php
<?php
namespace App\Livewire;

use Livewire\Attributes\Reactive;
use Illuminate\Support\Facades\AI;

class TutorChat extends Component {
    #[Reactive]
    public $questionId;
    
    public $messages = [];
    public $userInput = '';
    
    public function sendMessage() {
        $this->messages[] = ['role' => 'user', 'content' => $this->userInput];
        
        $response = AI::chat()
            ->asString()
            ->system('You are a helpful tutor')
            ->prompt($this->userInput)
            ->send();
        
        $this->messages[] = ['role' => 'assistant', 'content' => $response];
        $this->userInput = '';
        $this->dispatch('message-sent');
    }
}
```

---

## Key Concepts to Master

### 1. Prompt Engineering

**The Anatomy of a Good Prompt:**

```
[System Role] You are an expert educational analyst
[Task] Analyze the following quiz performance data
[Output Format] Provide JSON with: weak_areas, strong_areas, recommendations
[Constraints] Keep response under 500 words, use simple language
[Examples] Here's what good output looks like...
[Data] [actual data to analyze]
```

**Techniques**:

| Technique | Example | Use Case |
|-----------|---------|----------|
| **Few-Shot** | Provide examples of input/output | Complex tasks |
| **Chain-of-Thought** | "Think step by step..." | Reasoning tasks |
| **Role Playing** | "You are a tutor..." | Consistent personality |
| **Constraints** | "Keep under 100 words" | Controlled output |
| **JSON Mode** | "Return as JSON..." | Structured data |

**Resources**:
- OpenAI Prompt Engineering: https://platform.openai.com/docs/guides/prompt-engineering
- Anthropic's Prompt Library: https://docs.anthropic.com/claude/prompt-library

### 2. Token Optimization

**Why This Matters**:
- Every token costs money
- Long contexts take longer to process
- Affects app latency

**Optimization Techniques**:

```php
// ❌ Bad: Send entire quiz history
$response = AI::prompt("Analyze all quiz data: " . json_encode($allData));

// ✅ Good: Send only relevant data
$relevant = $quizzes
    ->where('created_at', '>=', now()->subDays(7))
    ->select(['id', 'title', 'score', 'time_spent'])
    ->get();

$response = AI::prompt("Analyze: " . json_encode($relevant));
```

**Counting Tokens**:
```php
// Estimate: 1 token ≈ 4 characters
$tokenCount = strlen($text) / 4;

// Exact: Use GPT tokenizer
// See: https://github.com/openai-php/tokens
```

### 3. Function Calling / Tool Use

**What It Is**: AI can request your app to do things

```php
// Define what AI can request
$functions = [
    [
        'name' => 'analyze_quiz',
        'description' => 'Analyze quiz performance',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'quiz_id' => ['type' => 'integer'],
                'metric' => ['type' => 'string', 'enum' => ['accuracy', 'speed', 'consistency']]
            ]
        ]
    ]
];

// AI decides to call this function
$response = AI::chat()
    ->withTools($functions)
    ->prompt('Analyze quiz 5 by accuracy')
    ->send();

// Check what AI wants to do
if ($response->function_call) {
    $result = match($response->function_call->name) {
        'analyze_quiz' => analyzeQuiz($response->function_call->arguments),
        default => null
    };
    
    // Send result back to AI
    $finalResponse = AI::chat()
        ->addMessage('assistant', $response)
        ->addMessage('function', $result)
        ->send();
}
```

### 4. Vector Embeddings & Semantic Search

**Concept**: Convert text to vectors for similarity matching

```php
// Example: Find similar quiz questions
class SimilarQuestionFinder {
    public function find($questionText, $limit = 5) {
        // Convert question to embedding (vector)
        $embedding = \OpenAI\Client::embeddings()
            ->create([
                'model' => 'text-embedding-3-small',
                'input' => $questionText
            ])
            ->embeddings[0]->embedding;
        
        // Find most similar questions in database
        // (requires storing embeddings in database)
        return Question::query()
            ->selectRaw('*, (embedding <=> ?::vector) as similarity', [$embedding])
            ->orderBy('similarity')
            ->limit($limit)
            ->get();
    }
}
```

**Use Cases**:
- Finding duplicate questions
- Recommending similar study materials
- Smart search

**Setup**:
- Use pgvector extension for PostgreSQL
- Or use Pinecone.io (cloud vector DB)
- Store embeddings in your database

### 5. RAG (Retrieval Augmented Generation)

**Problem**: AI has knowledge cutoff, doesn't know your specific data

**Solution**: Retrieve relevant documents, give to AI

```php
class SmartTutorWithRAG {
    public function answer($studentQuestion) {
        // 1. Find relevant study materials
        $relevant = $this->retrieveRelevantContent($studentQuestion);
        
        // 2. Create context from retrieved content
        $context = "Based on your course materials:\n";
        foreach ($relevant as $material) {
            $context .= "- {$material->title}: {$material->summary}\n";
        }
        
        // 3. Ask AI with context
        $response = AI::chat()
            ->system('Answer using the provided materials')
            ->prompt($context . "\n\nStudent question: {$studentQuestion}")
            ->send();
        
        return $response;
    }
}
```

---

## Implementation Roadmap

### Month 1: Foundation (Weeks 1-4)

**Week 1: Learn Basics**
- Take "Prompt Engineering for Developers" course (1 hour)
- Read OpenAI API basics (2 hours)
- Sign up for OpenAI API, get API key
- Understand token economics

**Week 2: First Integration**
- Install Laravel AI package
- Create simple chatbot
- Test on local environment
- Build first prompt templates

**Week 3: Cost Management**
- Implement usage tracking
- Set up cost alerts
- Optimize prompts for token efficiency
- Test with different models

**Week 4: Production Readiness**
- Add error handling
- Implement caching
- Set up monitoring
- Write documentation

### Month 2: Quiz Features (Weeks 5-8)

**Week 5: Quiz Analysis**
- Analyze student performance with AI
- Generate insights from quiz data
- Create performance reports

**Week 6: Question Generation**
- AI generates questions from content
- Validate generated questions
- Store in database

**Week 7: Difficulty Levels**
- AI adjusts question difficulty
- Adaptive quiz system
- Track student progression

**Week 8: Testing & Refinement**
- Test with real students
- Gather feedback
- Refine prompts

### Month 3: Tutoring (Weeks 9-12)

**Week 9: Interactive Tutoring**
- Build AI tutor interface
- Multi-turn conversations
- Hint system

**Week 10: Personalization**
- Adapt to student learning style
- Remember student preferences
- Suggest topics

**Week 11: Content Recommendations**
- Recommend videos based on performance
- Suggest practice areas
- Personalized learning path

**Week 12: Launch & Monitor**
- Beta test with students
- Fix issues
- Prepare for production

### Month 4: Advanced (Weeks 13+)

**Ideas**:
- RAG with course materials
- Vector embeddings for similarity
- Function calling for complex logic
- Multi-language support
- Offline capabilities

---

## AI Use Cases for Future Academy

### Immediate (Can Implement Now)

#### 1. **AI Student Chat Support**
- 24/7 answer common questions
- Reduce support burden
- Instant help for students
- Cost: ~$0.10 per student per week

#### 2. **Performance Analysis**
- Identify weak areas
- Generate insights
- Auto-send recommendations
- Cost: ~$0.05 per analysis

#### 3. **Question Enhancement**
- Improve explanations
- Add hints
- Generate similar questions
- Cost: ~$0.02 per enhancement

### Short-term (2-4 weeks)

#### 4. **Quiz Generation**
- Generate questions from materials
- Auto-create practice exams
- Batch generation at night
- Cost: ~$0.10 per 5 questions

#### 5. **Personalized Learning Paths**
- AI recommends next topics
- Adaptive difficulty
- Learning style matching
- Cost: ~$0.05 per student per day

### Medium-term (1-3 months)

#### 6. **Interactive Tutoring System**
- Multi-turn conversation
- Socratic method (guides to answer)
- Real-time help
- Cost: ~$0.10 per session

#### 7. **Content Recommendations**
- Recommend videos
- Suggest articles
- Personalized reading lists
- Cost: ~$0.02 per recommendation

### Long-term (3-6 months)

#### 8. **Advanced RAG System**
- Embed all course materials
- Semantic search
- Cross-subject recommendations
- Cost: One-time setup ~$100, then ~$0.01 per query

#### 9. **Plagiarism Detection**
- Detect copied content in essays
- Similarity to internet sources
- Student work benchmarking
- Cost: ~$0.05 per submission

#### 10. **Automated Grading**
- Grade essays with rubrics
- Provide feedback
- Consistency checking
- Cost: ~$0.10 per essay

---

## Communities & Support

### Official Communities

| Platform | Link | Best For |
|----------|------|----------|
| **OpenAI Community** | https://community.openai.com | API support |
| **Anthropic Discord** | https://discord.gg/anthropic | Claude help |
| **Hugging Face Community** | https://huggingface.co/spaces | Research |
| **Laravel Discord** | https://discord.gg/laravel | Framework help |

### Social Communities

| Platform | Follow | Content |
|----------|--------|---------|
| **r/MachineLearning** | https://reddit.com/r/MachineLearning | Research papers |
| **r/OpenAI** | https://reddit.com/r/openai | News & discussions |
| **Dev.to AI Tag** | https://dev.to/t/ai | Tutorials |
| **Twitter/X** | @OpenAI, @AnthropicAI | News |

### Blogs & Newsletters

| Source | Focus | Frequency |
|--------|-------|-----------|
| **OpenAI Blog** | Official updates | 2-3x per month |
| **Anthropic Blog** | Claude & safety | Monthly |
| **deeplearning.ai** Newsletter | News & insights | Weekly |
| **Import AI** Newsletter | Research summary | Weekly |

---

## Quick Reference

### Cost Estimator

```
Quiz Analysis:         ~$0.01 per analysis
Question Generation:   ~$0.02 per question
Student Chat:          ~$0.001 per message
Performance Report:    ~$0.05 per student
Tutoring Session:      ~$0.10 per 30 min

For 1000 students:
- Monthly Q&A chatbot cost: ~$10
- Monthly analysis cost: ~$20
- Monthly generation cost: ~$30
Total: ~$60/month for 1000 students
```

### Model Selection Quick Guide

```
Task: Simple question answering
→ Use: GPT-3.5-turbo (fast & cheap)

Task: Complex analysis or reasoning
→ Use: GPT-4-turbo (slower but smarter)

Task: Explanation & teaching
→ Use: Claude 3 (excellent explanations)

Task: Very long documents
→ Use: Claude 3 Opus (128K context)

Task: Semantic search / similarity
→ Use: text-embedding-3-small (cheap)
```

### Implementation Template

```php
<?php
// 1. Setup
use Illuminate\Support\Facades\AI;

// 2. Create service
class MyAIFeature {
    public function process($input) {
        // 3. Build prompt
        $prompt = "Your system prompt and instructions...";
        
        // 4. Call AI
        $response = AI::chat()
            ->system($prompt)
            ->prompt($input)
            ->send();
        
        // 5. Parse response
        $result = $this->parseResponse($response);
        
        // 6. Store/return
        return $result;
    }
}

// 3. Use in routes/controllers
Route::get('/ai-feature', function () {
    return app(MyAIFeature::class)->process(request('input'));
});
```

---

## Getting Help

### Debugging AI Issues

**AI gave wrong answer?**
1. Check token limit (context window)
2. Check temperature setting
3. Try adding more examples in prompt
4. Try a different model

**API rate limited?**
1. Add exponential backoff
2. Queue requests
3. Cache common requests
4. Upgrade API quota

**Too expensive?**
1. Use cheaper model (GPT-3.5 instead of GPT-4)
2. Reduce context size
3. Cache responses
4. Batch requests

### Resources by Time Investment

**30 Minutes**: 
- Skim official docs
- Watch Fireship video on LLMs

**2 Hours**:
- Complete DeepLearning.AI course
- Build simple chatbot

**1 Day**:
- Full OpenAI API tutorial
- Build 3 AI features for your app

**1 Week**:
- Complete Hugging Face course
- Production-ready implementation

**1 Month**:
- Become proficient
- Implement multiple AI features
- Understand optimization & costs

---

## Next Steps (Recommended)

### Today
- [ ] Read this document carefully
- [ ] Sign up for OpenAI API
- [ ] Install Laravel AI package

### This Week
- [ ] Take "Prompt Engineering for Developers"
- [ ] Build hello-world chatbot
- [ ] Understand token economics

### Next Week
- [ ] Implement AI chat support
- [ ] Test with real students
- [ ] Monitor costs

### Next Month
- [ ] Generate quiz questions with AI
- [ ] Implement performance analysis
- [ ] Build tutoring system

---

## Summary

| Item | Value |
|------|-------|
| **Time to basic proficiency** | 2-4 weeks |
| **Cost to implement first feature** | $5-20 API setup |
| **ROI** | High (saves teacher time, improves learning) |
| **Difficulty** | Medium (good docs available) |
| **Scalability** | Excellent (pay per use) |

---

## Appendix: Useful Commands

```bash
# Check token count (estimate)
# 1 token ≈ 4 characters
php -r "echo strlen('your text here') / 4;"

# Install Laravel AI
composer require laravel/ai

# Test OpenAI connection
php artisan tinker
> AI::chat()->asString()->prompt('Hello!')->send()

# Monitor API usage
# Dashboard: https://platform.openai.com/account/usage/overview
```

---

**Last Updated**: February 15, 2026  
**Version**: 1.0  
**Status**: Complete & Ready to Use  

For questions or updates, refer to official documentation links in this guide.
