<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class JambBiologyBatchSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $jambExam = ExamType::where('slug', 'jamb')->first();
        $subject = Subject::where('name', 'Biology')->first();

        if (!$admin || !$jambExam || !$subject) {
            $this->command->error('Required data not found.');
            return;
        }

        $questions = [
            ['text' => 'What is the powerhouse of the cell?', 'options' => [['A', 'Nucleus', false], ['B', 'Mitochondria', true], ['C', 'Ribosome', false], ['D', 'Chloroplast', false]], 'explanation' => 'Mitochondria produce energy (ATP) for the cell', 'year' => 2022],
            ['text' => 'Which organ pumps blood throughout the body?', 'options' => [['A', 'Lungs', false], ['B', 'Liver', false], ['C', 'Heart', true], ['D', 'Kidney', false]], 'explanation' => 'The heart pumps blood through the circulatory system', 'year' => 2022],
            ['text' => 'What is the process by which plants make food?', 'options' => [['A', 'Respiration', false], ['B', 'Digestion', false], ['C', 'Photosynthesis', true], ['D', 'Transpiration', false]], 'explanation' => 'Photosynthesis uses sunlight, water, and CO2 to produce glucose', 'year' => 2022],
            ['text' => 'What is the largest organ in the human body?', 'options' => [['A', 'Liver', false], ['B', 'Brain', false], ['C', 'Skin', true], ['D', 'Lungs', false]], 'explanation' => 'The skin is the largest organ by surface area', 'year' => 2022],
            ['text' => 'How many chromosomes do humans have?', 'options' => [['A', '23', false], ['B', '46', true], ['C', '48', false], ['D', '92', false]], 'explanation' => 'Humans have 46 chromosomes (23 pairs)', 'year' => 2022],
            ['text' => 'Which blood type is the universal donor?', 'options' => [['A', 'A', false], ['B', 'B', false], ['C', 'AB', false], ['D', 'O', true]], 'explanation' => 'Type O blood can be donated to all blood types', 'year' => 2022],
            ['text' => 'What is the functional unit of the kidney?', 'options' => [['A', 'Neuron', false], ['B', 'Nephron', true], ['C', 'Alveolus', false], ['D', 'Villus', false]], 'explanation' => 'Nephron is the filtering unit of the kidney', 'year' => 2022],
            ['text' => 'Which vitamin is produced by the skin?', 'options' => [['A', 'Vitamin A', false], ['B', 'Vitamin C', false], ['C', 'Vitamin D', true], ['D', 'Vitamin K', false]], 'explanation' => 'Vitamin D is produced when skin is exposed to sunlight', 'year' => 2022],
            ['text' => 'What is the study of plants called?', 'options' => [['A', 'Zoology', false], ['B', 'Botany', true], ['C', 'Ecology', false], ['D', 'Genetics', false]], 'explanation' => 'Botany is the scientific study of plants', 'year' => 2022],
            ['text' => 'Which organelle controls cell activities?', 'options' => [['A', 'Nucleus', true], ['B', 'Mitochondria', false], ['C', 'Ribosome', false], ['D', 'Golgi body', false]], 'explanation' => 'The nucleus contains DNA and controls cell functions', 'year' => 2022],
            ['text' => 'What is the normal human body temperature?', 'options' => [['A', '35°C', false], ['B', '37°C', true], ['C', '39°C', false], ['D', '40°C', false]], 'explanation' => 'Normal body temperature is approximately 37°C or 98.6°F', 'year' => 2022],
            ['text' => 'Which gas do plants release during photosynthesis?', 'options' => [['A', 'Carbon dioxide', false], ['B', 'Nitrogen', false], ['C', 'Oxygen', true], ['D', 'Hydrogen', false]], 'explanation' => 'Plants release oxygen as a byproduct of photosynthesis', 'year' => 2022],
            ['text' => 'What is the basic unit of life?', 'options' => [['A', 'Tissue', false], ['B', 'Cell', true], ['C', 'Organ', false], ['D', 'Organism', false]], 'explanation' => 'The cell is the smallest unit of life', 'year' => 2022],
            ['text' => 'Which type of blood vessel carries blood away from the heart?', 'options' => [['A', 'Vein', false], ['B', 'Artery', true], ['C', 'Capillary', false], ['D', 'Venule', false]], 'explanation' => 'Arteries carry oxygenated blood away from the heart', 'year' => 2022],
            ['text' => 'What is the green pigment in plants?', 'options' => [['A', 'Carotene', false], ['B', 'Chlorophyll', true], ['C', 'Xanthophyll', false], ['D', 'Melanin', false]], 'explanation' => 'Chlorophyll gives plants their green color and absorbs light', 'year' => 2022],
            ['text' => 'How many chambers does the human heart have?', 'options' => [['A', '2', false], ['B', '3', false], ['C', '4', true], ['D', '5', false]], 'explanation' => 'The human heart has 4 chambers: 2 atria and 2 ventricles', 'year' => 2022],
            ['text' => 'What is the main function of red blood cells?', 'options' => [['A', 'Fight infection', false], ['B', 'Carry oxygen', true], ['C', 'Clot blood', false], ['D', 'Produce antibodies', false]], 'explanation' => 'Red blood cells carry oxygen using hemoglobin', 'year' => 2022],
            ['text' => 'Which organ produces insulin?', 'options' => [['A', 'Liver', false], ['B', 'Pancreas', true], ['C', 'Stomach', false], ['D', 'Kidney', false]], 'explanation' => 'The pancreas produces insulin to regulate blood sugar', 'year' => 2022],
            ['text' => 'What is the process of cell division called?', 'options' => [['A', 'Photosynthesis', false], ['B', 'Mitosis', true], ['C', 'Osmosis', false], ['D', 'Diffusion', false]], 'explanation' => 'Mitosis is the process of cell division', 'year' => 2022],
            ['text' => 'Which part of the brain controls balance?', 'options' => [['A', 'Cerebrum', false], ['B', 'Cerebellum', true], ['C', 'Medulla', false], ['D', 'Hypothalamus', false]], 'explanation' => 'The cerebellum coordinates balance and movement', 'year' => 2022],
            ['text' => 'What is the lifespan of human red blood cells?', 'options' => [['A', '30 days', false], ['B', '60 days', false], ['C', '120 days', true], ['D', '180 days', false]], 'explanation' => 'Red blood cells live approximately 120 days', 'year' => 2023],
            ['text' => 'Which enzyme digests protein?', 'options' => [['A', 'Amylase', false], ['B', 'Lipase', false], ['C', 'Pepsin', true], ['D', 'Maltase', false]], 'explanation' => 'Pepsin breaks down proteins in the stomach', 'year' => 2023],
            ['text' => 'What is the chemical symbol for glucose?', 'options' => [['A', 'C6H12O6', true], ['B', 'CO2', false], ['C', 'H2O', false], ['D', 'O2', false]], 'explanation' => 'Glucose has the formula C6H12O6', 'year' => 2023],
            ['text' => 'Which blood cells fight infection?', 'options' => [['A', 'Red blood cells', false], ['B', 'White blood cells', true], ['C', 'Platelets', false], ['D', 'Plasma cells', false]], 'explanation' => 'White blood cells defend against pathogens', 'year' => 2023],
            ['text' => 'What is the process of water loss from plants?', 'options' => [['A', 'Photosynthesis', false], ['B', 'Respiration', false], ['C', 'Transpiration', true], ['D', 'Digestion', false]], 'explanation' => 'Transpiration is water evaporation from plant surfaces', 'year' => 2023],
            ['text' => 'Which organ filters blood?', 'options' => [['A', 'Liver', false], ['B', 'Kidney', true], ['C', 'Spleen', false], ['D', 'Stomach', false]], 'explanation' => 'Kidneys filter waste from blood to produce urine', 'year' => 2023],
            ['text' => 'What is the largest bone in the human body?', 'options' => [['A', 'Humerus', false], ['B', 'Tibia', false], ['C', 'Femur', true], ['D', 'Fibula', false]], 'explanation' => 'The femur (thigh bone) is the longest and strongest bone', 'year' => 2023],
            ['text' => 'Which part of the eye detects light?', 'options' => [['A', 'Cornea', false], ['B', 'Lens', false], ['C', 'Retina', true], ['D', 'Iris', false]], 'explanation' => 'The retina contains light-sensitive cells (rods and cones)', 'year' => 2023],
            ['text' => 'What is the male reproductive cell called?', 'options' => [['A', 'Ovum', false], ['B', 'Sperm', true], ['C', 'Zygote', false], ['D', 'Gamete', false]], 'explanation' => 'Sperm is the male gamete', 'year' => 2023],
            ['text' => 'Which vitamin prevents scurvy?', 'options' => [['A', 'Vitamin A', false], ['B', 'Vitamin B', false], ['C', 'Vitamin C', true], ['D', 'Vitamin D', false]], 'explanation' => 'Vitamin C (ascorbic acid) prevents scurvy', 'year' => 2023],
            ['text' => 'What is the function of the liver?', 'options' => [['A', 'Pump blood', false], ['B', 'Filter air', false], ['C', 'Detoxify blood', true], ['D', 'Digest food', false]], 'explanation' => 'The liver detoxifies blood and produces bile', 'year' => 2023],
            ['text' => 'Which gas is needed for respiration?', 'options' => [['A', 'Nitrogen', false], ['B', 'Carbon dioxide', false], ['C', 'Oxygen', true], ['D', 'Hydrogen', false]], 'explanation' => 'Oxygen is essential for cellular respiration', 'year' => 2023],
            ['text' => 'What connects bone to bone?', 'options' => [['A', 'Tendon', false], ['B', 'Ligament', true], ['C', 'Cartilage', false], ['D', 'Muscle', false]], 'explanation' => 'Ligaments connect bones at joints', 'year' => 2023],
            ['text' => 'Which organ produces bile?', 'options' => [['A', 'Pancreas', false], ['B', 'Liver', true], ['C', 'Stomach', false], ['D', 'Gallbladder', false]], 'explanation' => 'The liver produces bile for fat digestion', 'year' => 2023],
            ['text' => 'What is the function of hemoglobin?', 'options' => [['A', 'Fight disease', false], ['B', 'Carry oxygen', true], ['C', 'Clot blood', false], ['D', 'Digest food', false]], 'explanation' => 'Hemoglobin in red blood cells transports oxygen', 'year' => 2023],
            ['text' => 'Which system removes waste from the body?', 'options' => [['A', 'Circulatory', false], ['B', 'Respiratory', false], ['C', 'Excretory', true], ['D', 'Digestive', false]], 'explanation' => 'The excretory system eliminates metabolic waste', 'year' => 2023],
            ['text' => 'What is the function of the small intestine?', 'options' => [['A', 'Store food', false], ['B', 'Absorb nutrients', true], ['C', 'Pump blood', false], ['D', 'Filter air', false]], 'explanation' => 'The small intestine absorbs nutrients from digested food', 'year' => 2023],
            ['text' => 'Which hormone regulates blood sugar?', 'options' => [['A', 'Adrenaline', false], ['B', 'Insulin', true], ['C', 'Thyroxine', false], ['D', 'Testosterone', false]], 'explanation' => 'Insulin lowers blood glucose levels', 'year' => 2023],
            ['text' => 'What is the scientific name for humans?', 'options' => [['A', 'Homo erectus', false], ['B', 'Homo sapiens', true], ['C', 'Homo habilis', false], ['D', 'Homo neanderthalensis', false]], 'explanation' => 'Humans are classified as Homo sapiens', 'year' => 2023],
            ['text' => 'Which organelle synthesizes proteins?', 'options' => [['A', 'Mitochondria', false], ['B', 'Ribosome', true], ['C', 'Nucleus', false], ['D', 'Lysosome', false]], 'explanation' => 'Ribosomes are the sites of protein synthesis', 'year' => 2023],
        ];

        $this->command->info('Seeding 40 Biology questions...');
        $this->createQuestions($questions, $subject, $jambExam, $admin);
        $this->command->info('Biology seeding complete!');
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
