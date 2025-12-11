<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class JambEnglishBatchSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $jambExam = ExamType::where('slug', 'jamb')->first();
        $subject = Subject::where('name', 'English Language')->first();

        if (!$admin || !$jambExam || !$subject) {
            $this->command->error('Required data not found.');
            return;
        }

        $questions = [
            ['text' => 'Choose the opposite of "generous"', 'options' => [['A', 'Kind', false], ['B', 'Selfish', true], ['C', 'Wealthy', false], ['D', 'Happy', false]], 'explanation' => 'Selfish means unwilling to share', 'year' => 2022],
            ['text' => 'Identify the correct sentence', 'options' => [['A', 'She don\'t like it', false], ['B', 'She doesn\'t like it', true], ['C', 'She not like it', false], ['D', 'She doesn\'t likes it', false]], 'explanation' => 'Correct form: doesn\'t + base verb', 'year' => 2022],
            ['text' => 'What is the plural of "child"?', 'options' => [['A', 'childs', false], ['B', 'childes', false], ['C', 'children', true], ['D', 'childrens', false]], 'explanation' => 'Irregular plural form', 'year' => 2022],
            ['text' => 'Choose the synonym of "happy"', 'options' => [['A', 'Sad', false], ['B', 'Angry', false], ['C', 'Joyful', true], ['D', 'Tired', false]], 'explanation' => 'Joyful means happy', 'year' => 2022],
            ['text' => 'Which is a pronoun?', 'options' => [['A', 'Run', false], ['B', 'Quick', false], ['C', 'He', true], ['D', 'Book', false]], 'explanation' => 'He is a personal pronoun', 'year' => 2022],
            ['text' => 'Choose the correct: "Its a ___ day"', 'options' => [['A', 'beautifull', false], ['B', 'beautiful', true], ['C', 'beutiful', false], ['D', 'beautful', false]], 'explanation' => 'Correct spelling: beautiful', 'year' => 2022],
            ['text' => 'What is the past tense of "go"?', 'options' => [['A', 'goed', false], ['B', 'went', true], ['C', 'gone', false], ['D', 'going', false]], 'explanation' => 'Irregular past tense', 'year' => 2022],
            ['text' => 'Choose the correct article: "___ apple"', 'options' => [['A', 'A', false], ['B', 'An', true], ['C', 'The', false], ['D', 'No article', false]], 'explanation' => 'Use "an" before vowel sounds', 'year' => 2022],
            ['text' => 'Which is an adjective?', 'options' => [['A', 'Quickly', false], ['B', 'Blue', true], ['C', 'Run', false], ['D', 'She', false]], 'explanation' => 'Blue describes a noun', 'year' => 2022],
            ['text' => 'Choose the correct: "They ___ students"', 'options' => [['A', 'is', false], ['B', 'am', false], ['C', 'are', true], ['D', 'be', false]], 'explanation' => 'Use "are" with plural subjects', 'year' => 2022],
            ['text' => 'What is the opposite of "fast"?', 'options' => [['A', 'Quick', false], ['B', 'Slow', true], ['C', 'Speed', false], ['D', 'Run', false]], 'explanation' => 'Slow is the opposite of fast', 'year' => 2022],
            ['text' => 'Choose the correct preposition: "on ___ table"', 'options' => [['A', 'at', false], ['B', 'in', false], ['C', 'the', true], ['D', 'of', false]], 'explanation' => 'Use "on the table"', 'year' => 2022],
            ['text' => 'Which is a verb?', 'options' => [['A', 'Happy', false], ['B', 'Book', false], ['C', 'Sing', true], ['D', 'Beautiful', false]], 'explanation' => 'Sing is an action word', 'year' => 2022],
            ['text' => 'Choose the correct: "I ___ to school"', 'options' => [['A', 'goes', false], ['B', 'go', true], ['C', 'going', false], ['D', 'gone', false]], 'explanation' => 'Use base form with "I"', 'year' => 2022],
            ['text' => 'What is the plural of "box"?', 'options' => [['A', 'boxs', false], ['B', 'boxes', true], ['C', 'boxies', false], ['D', 'boxen', false]], 'explanation' => 'Add -es to words ending in x', 'year' => 2022],
            ['text' => 'Choose the synonym of "big"', 'options' => [['A', 'Small', false], ['B', 'Large', true], ['C', 'Tiny', false], ['D', 'Little', false]], 'explanation' => 'Large means big', 'year' => 2022],
            ['text' => 'What is the past tense of "eat"?', 'options' => [['A', 'eated', false], ['B', 'ate', true], ['C', 'eaten', false], ['D', 'eating', false]], 'explanation' => 'Irregular verb: eat-ate-eaten', 'year' => 2022],
            ['text' => 'Choose the correct: "She ___ a teacher"', 'options' => [['A', 'am', false], ['B', 'is', true], ['C', 'are', false], ['D', 'be', false]], 'explanation' => 'Use "is" with singular subjects', 'year' => 2022],
            ['text' => 'Which word is a noun?', 'options' => [['A', 'Run', false], ['B', 'Quick', false], ['C', 'Table', true], ['D', 'Happily', false]], 'explanation' => 'Table is a person, place, or thing', 'year' => 2022],
            ['text' => 'Choose opposite of "hot"', 'options' => [['A', 'Warm', false], ['B', 'Cold', true], ['C', 'Spicy', false], ['D', 'Fire', false]], 'explanation' => 'Cold is opposite of hot', 'year' => 2022],
            ['text' => 'What is the plural of "man"?', 'options' => [['A', 'mans', false], ['B', 'men', true], ['C', 'mans', false], ['D', 'mens', false]], 'explanation' => 'Irregular plural: man-men', 'year' => 2023],
            ['text' => 'Choose correct: "He ___ not here"', 'options' => [['A', 'am', false], ['B', 'is', true], ['C', 'are', false], ['D', 'be', false]], 'explanation' => 'He is (He\'s)', 'year' => 2023],
            ['text' => 'Which is an adverb?', 'options' => [['A', 'Quick', false], ['B', 'Quickly', true], ['C', 'Quickness', false], ['D', 'Quicker', false]], 'explanation' => 'Quickly describes how (adverb)', 'year' => 2023],
            ['text' => 'Choose the correct: "___ books are these?"', 'options' => [['A', 'Who', false], ['B', 'Whose', true], ['C', 'Whom', false], ['D', 'Which', false]], 'explanation' => 'Whose shows possession', 'year' => 2023],
            ['text' => 'What is the past tense of "run"?', 'options' => [['A', 'runned', false], ['B', 'ran', true], ['C', 'run', false], ['D', 'running', false]], 'explanation' => 'Irregular: run-ran-run', 'year' => 2023],
            ['text' => 'Choose synonym of "smart"', 'options' => [['A', 'Stupid', false], ['B', 'Intelligent', true], ['C', 'Dumb', false], ['D', 'Slow', false]], 'explanation' => 'Intelligent means smart', 'year' => 2023],
            ['text' => 'Choose correct: "I have ___ apple"', 'options' => [['A', 'a', false], ['B', 'an', true], ['C', 'the', false], ['D', 'no article', false]], 'explanation' => 'Use "an" before vowel sounds', 'year' => 2023],
            ['text' => 'What is the plural of "tooth"?', 'options' => [['A', 'tooths', false], ['B', 'teeth', true], ['C', 'toothes', false], ['D', 'teeths', false]], 'explanation' => 'Irregular: tooth-teeth', 'year' => 2023],
            ['text' => 'Choose opposite of "early"', 'options' => [['A', 'Late', true], ['B', 'Soon', false], ['C', 'Quick', false], ['D', 'Fast', false]], 'explanation' => 'Late is opposite of early', 'year' => 2023],
            ['text' => 'Which is correct? "Between you and ___"', 'options' => [['A', 'I', false], ['B', 'me', true], ['C', 'myself', false], ['D', 'mine', false]], 'explanation' => 'Use object pronoun after preposition', 'year' => 2023],
            ['text' => 'Choose the correct: "She ___ very well"', 'options' => [['A', 'sing', false], ['B', 'sings', true], ['C', 'singing', false], ['D', 'sang', false]], 'explanation' => 'Add -s for third person singular', 'year' => 2023],
            ['text' => 'What is synonym of "difficult"?', 'options' => [['A', 'Easy', false], ['B', 'Simple', false], ['C', 'Hard', true], ['D', 'Soft', false]], 'explanation' => 'Hard means difficult', 'year' => 2023],
            ['text' => 'Choose correct: "There are ___ students"', 'options' => [['A', 'much', false], ['B', 'many', true], ['C', 'a', false], ['D', 'an', false]], 'explanation' => 'Use "many" with countable plurals', 'year' => 2023],
            ['text' => 'What is the plural of "mouse"?', 'options' => [['A', 'mouses', false], ['B', 'mice', true], ['C', 'mices', false], ['D', 'mouse', false]], 'explanation' => 'Irregular: mouse-mice', 'year' => 2023],
            ['text' => 'Choose opposite of "strong"', 'options' => [['A', 'Weak', true], ['B', 'Power', false], ['C', 'Might', false], ['D', 'Force', false]], 'explanation' => 'Weak is opposite of strong', 'year' => 2023],
            ['text' => 'Which is correct? "Neither he ___ I"', 'options' => [['A', 'or', false], ['B', 'nor', true], ['C', 'and', false], ['D', 'but', false]], 'explanation' => 'Neither...nor (correlative conjunction)', 'year' => 2023],
            ['text' => 'Choose correct: "This is ___ book"', 'options' => [['A', 'her', true], ['B', 'she', false], ['C', 'hers', false], ['D', 'herself', false]], 'explanation' => 'Use possessive adjective before noun', 'year' => 2023],
            ['text' => 'What is the past tense of "write"?', 'options' => [['A', 'writed', false], ['B', 'wrote', true], ['C', 'written', false], ['D', 'writing', false]], 'explanation' => 'Irregular: write-wrote-written', 'year' => 2023],
            ['text' => 'Choose synonym of "begin"', 'options' => [['A', 'End', false], ['B', 'Start', true], ['C', 'Finish', false], ['D', 'Stop', false]], 'explanation' => 'Start means begin', 'year' => 2023],
            ['text' => 'Choose correct: "___ you like tea?"', 'options' => [['A', 'Does', false], ['B', 'Do', true], ['C', 'Is', false], ['D', 'Are', false]], 'explanation' => 'Use "Do" with you/we/they', 'year' => 2023],
        ];

        $this->command->info('Seeding 40 English Language questions...');
        $this->createQuestions($questions, $subject, $jambExam, $admin);
        $this->command->info('English Language seeding complete!');
    }

    private function createQuestions(array $questions, $subject, $examType, $admin): void
    {
        foreach ($questions as $q) {
            $question = Question::create([
                'question_text' => $q['text'],
                'explanation' => $q['explanation'],
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'exam_year' => $q['year'],
                'difficulty' => 'medium',
                'status' => 'approved',
                'is_active' => true,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            foreach ($q['options'] as $index => $opt) {
                $question->options()->create([
                    'label' => $opt[0],
                    'option_text' => $opt[1],
                    'is_correct' => $opt[2],
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
