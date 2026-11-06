<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Models\TestSeries;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    /**
     * Idempotent local demo-content seeder.
     *
     * Creates 12 subjects, 12 published courses, 12 published test series,
     * 12 exams, 60 MCQs, and 12 generated PDF study notes.
     * Does not create students, enrollments, payments, or overwrite unrelated data.
     */
    public function run(): void
    {
        $adminId = DB::table('users')
            ->whereIn('id', DB::table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('roles.slug', 'super-admin')
                ->select('role_user.user_id'))
            ->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        $catalog = $this->catalog();
        $counts = ['subjects' => 0, 'courses' => 0, 'series' => 0, 'exams' => 0, 'questions' => 0, 'materials' => 0];

        foreach ($catalog as $item) {
            DB::transaction(function () use ($item, $adminId, &$counts) {
                $slug = Str::slug($item['name']);
                $subject = Subject::updateOrCreate(
                    ['slug' => 'demo-'.$slug],
                    ['name' => $item['name'].' (Demo)', 'description' => 'Demo subject for local ExamTestHub testing.', 'is_active' => true]
                );
                $counts['subjects']++;

                $topic = Topic::updateOrCreate(
                    ['subject_id' => $subject->id, 'slug' => 'demo-basics'],
                    ['name' => 'Fundamentals', 'description' => 'Introductory practice topics.', 'is_active' => true]
                );

                $course = Course::updateOrCreate(
                    ['slug' => 'demo-course-'.$slug],
                    [
                        'title' => $item['name'].' - Foundation Course (Demo)',
                        'title_hi' => $item['name'].' - आधारभूत पाठ्यक्रम (डेमो)',
                        'description' => 'A sample bilingual foundation course for testing course catalogue, lesson navigation and student progress.',
                        'description_hi' => 'कोर्स कैटलॉग, पाठ और विद्यार्थी प्रगति की जाँच के लिए नमूना पाठ्यक्रम।',
                        'category' => 'Competitive Exams',
                        'access_type' => 'free',
                        'price' => 0,
                        'currency' => 'INR',
                        'status' => 'published',
                        'published_at' => now(),
                        'created_by' => $adminId,
                    ]
                );
                $counts['courses']++;

                $section = CourseSection::updateOrCreate(
                    ['course_id' => $course->id, 'position' => 1],
                    ['title' => 'Getting Started', 'title_hi' => 'शुरुआत करें', 'description' => 'Introduction and core concepts.']
                );
                $chapter = CourseChapter::updateOrCreate(
                    ['section_id' => $section->id, 'position' => 1],
                    ['title' => 'Core Fundamentals', 'title_hi' => 'मुख्य आधार']
                );
                CourseLesson::updateOrCreate(
                    ['chapter_id' => $chapter->id, 'position' => 1],
                    [
                        'title' => 'Introduction to '.$item['name'],
                        'title_hi' => $item['name'].' का परिचय',
                        'description' => 'Demo lesson covering the subject overview and study approach.',
                        'description_hi' => 'विषय का परिचय और अध्ययन पद्धति।',
                        'type' => 'text',
                        'content' => $item['lesson'],
                        'content_hi' => 'यह डेमो पाठ है। मुख्य अवधारणाओं को पढ़ें, उदाहरणों को समझें और अंत में अभ्यास प्रश्न हल करें।',
                        'is_preview' => true,
                        'duration_minutes' => 20,
                        'status' => 'published',
                    ]
                );

                $questions = [];
                foreach ($item['questions'] as $qIndex => $q) {
                    $question = Question::updateOrCreate(
                        ['subject_id' => $subject->id, 'prompt' => '[DEMO] '.$q[0]],
                        [
                            'topic_id' => $topic->id,
                            'created_by' => $adminId,
                            'type' => 'single_choice',
                            'options' => ['A' => $q[1][0], 'B' => $q[1][1], 'C' => $q[1][2], 'D' => $q[1][3]],
                            'correct_answer' => [$q[2]],
                            'explanation' => $q[3],
                            'difficulty' => $qIndex < 2 ? 'easy' : 'medium',
                            'language' => 'en',
                            'marks' => 1,
                            'negative_marks' => 0.25,
                            'status' => 'published',
                        ]
                    );
                    $questions[] = $question;
                    $counts['questions']++;
                }

                $exam = Exam::updateOrCreate(
                    ['slug' => 'demo-exam-'.$slug],
                    [
                        'title' => $item['name'].' Practice Exam (Demo)',
                        'description' => 'Five sample multiple-choice questions for local testing.',
                        'instructions' => 'This is demo content. Each question carries 1 mark. Wrong answers carry 0.25 negative marks.',
                        'duration_minutes' => 10,
                        'max_attempts' => 5,
                        'shuffle_questions' => true,
                        'shuffle_options' => false,
                        'status' => 'published',
                        'created_by' => $adminId,
                    ]
                );
                $exam->questions()->sync(collect($questions)->mapWithKeys(fn ($q, $position) => [
                    $q->id => ['position' => $position + 1, 'marks' => 1, 'negative_marks' => 0.25],
                ])->all());
                $counts['exams']++;

                $series = TestSeries::updateOrCreate(
                    ['slug' => 'demo-series-'.$slug],
                    [
                        'title' => $item['name'].' Test Series (Demo)',
                        'description' => 'A free local demo test series containing one practice exam and five sample questions.',
                        'access_type' => 'free',
                        'price' => 0,
                        'currency' => 'INR',
                        'status' => 'published',
                        'created_by' => $adminId,
                    ]
                );
                $series->exams()->sync([$exam->id => ['position' => 1]]);
                $counts['series']++;

                $pdfPath = 'demo/study-materials/'.$slug.'-demo-notes.pdf';
                $pdf = $this->makePdf($item['name'].' - Demo Study Notes', [
                    'EXAMTESTHUB - LOCAL DEMO STUDY MATERIAL',
                    '',
                    'Subject: '.$item['name'],
                    'Material type: Sample PDF notes',
                    'Purpose: Local UI, download and access-control testing only.',
                    '',
                    'OVERVIEW',
                    $item['summary'],
                    '',
                    'KEY STUDY POINTS',
                    ...$item['points'],
                    '',
                    'PRACTICE GUIDANCE',
                    'Read each concept carefully. Make short notes, revise definitions,',
                    'and attempt the related demo test series after completing the lesson.',
                    '',
                    'IMPORTANT',
                    'This document is synthetic demo content, not an official publication',
                    'or a complete exam-preparation resource.',
                ]);
                Storage::disk('local')->put($pdfPath, $pdf);

                StudyMaterial::updateOrCreate(
                    ['file_path' => $pdfPath],
                    [
                        'title' => $item['name'].' - Demo Study Notes',
                        'title_hi' => $item['name'].' - डेमो अध्ययन नोट्स',
                        'description' => 'Generated sample PDF for local testing of the study-material library.',
                        'description_hi' => 'स्टडी मटेरियल लाइब्रेरी की जाँच के लिए नमूना PDF।',
                        'subject_id' => $subject->id,
                        // General-library demo PDFs remain downloadable without a course enrollment.
                        'course_id' => null,
                        'language' => 'en',
                        'status' => 'published',
                        'original_name' => $slug.'-demo-notes.pdf',
                        'mime_type' => 'application/pdf',
                        'file_size' => strlen($pdf),
                        'published_at' => now(),
                        'created_by' => $adminId,
                    ]
                );
                $counts['materials']++;
            });
        }

        $this->command?->info('Demo content seeded successfully: '.json_encode($counts));
        $this->command?->warn('All records are labelled Demo. No payments or student enrollments were created.');
    }

    private function makePdf(string $title, array $lines): string
    {
        $all = [$title, str_repeat('-', min(75, strlen($title)))];
        foreach ($lines as $line) {
            $line = $this->ascii((string) $line);
            foreach (explode("\n", wordwrap($line, 88, "\n", true)) as $wrapped) {
                $all[] = $wrapped;
            }
        }

        $chunks = array_chunk($all, 48);
        $objects = [];
        $pageIds = [];
        $fontId = 3 + (count($chunks) * 2);
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        foreach ($chunks as $i => $chunk) {
            $pageId = 3 + ($i * 2);
            $streamId = $pageId + 1;
            $pageIds[] = $pageId.' 0 R';
            $stream = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
            foreach ($chunk as $line) {
                $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                $stream .= '('.$escaped.") Tj\nT*\n";
            }
            $stream .= "ET";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 '.$fontId.' 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $objects[$streamId] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageIds).'] /Count '.count($pageIds).' >>';
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 ".($max + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size ".($max + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
        return $pdf;
    }

    private function ascii(string $value): string
    {
        $value = Str::ascii($value);
        return preg_replace('/[^\x20-\x7E]/', '', $value) ?? '';
    }

    private function catalog(): array
    {
        return [
            [
                'name' => 'General Knowledge',
                'summary' => 'General awareness includes important facts about India, institutions, awards, events and basic static knowledge.',
                'lesson' => 'Study static GK in categories: national symbols, constitutional bodies, important days, awards, sports and major institutions. Maintain a revision notebook.',
                'points' => ['Separate static facts from current affairs.', 'Revise important national institutions and their roles.', 'Use reliable sources and note the date for current-affairs facts.'],
                'questions' => [
                    ['Which is the national animal of India?', ['Bengal tiger', 'Asiatic lion', 'Elephant', 'Leopard'], 'A', 'The Bengal tiger is the national animal of India.'],
                    ['What is the capital of India?', ['Mumbai', 'New Delhi', 'Kolkata', 'Chennai'], 'B', 'New Delhi is the capital of India.'],
                    ['Which is the highest civilian award in India?', ['Padma Shri', 'Padma Bhushan', 'Bharat Ratna', 'Param Vir Chakra'], 'C', 'Bharat Ratna is the highest civilian award of India.'],
                    ['The National Anthem of India was written by whom?', ['Bankim Chandra', 'Rabindranath Tagore', 'Sarojini Naidu', 'Subramania Bharati'], 'B', 'Rabindranath Tagore wrote Jana Gana Mana.'],
                    ['Which organisation publishes the Human Development Report?', ['UNDP', 'UNESCO', 'WHO', 'WTO'], 'A', 'The United Nations Development Programme publishes the Human Development Report.'],
                ],
            ],
            [
                'Indian History',
                'Indian history is studied through ancient, medieval and modern periods, including social, cultural and freedom movements.',
                'Organise history chronologically. Focus on major dynasties, administrative systems, reform movements, the revolt of 1857 and the national movement.',
                ['Ancient India: Maurya, Gupta and major cultural developments.', 'Medieval India: Sultanate and Mughal administration.', 'Modern India: 1857, social reform and freedom struggle.'],
                [
                    ['Who founded the Maurya Empire?', ['Ashoka', 'Chandragupta Maurya', 'Bindusara', 'Harsha'], 'B', 'Chandragupta Maurya founded the Maurya Empire.'],
                    ['The Great Bath was discovered at which site?', ['Harappa', 'Lothal', 'Mohenjo-daro', 'Kalibangan'], 'C', 'The Great Bath is a major structure at Mohenjo-daro.'],
                    ['Who was the first Mughal emperor in India?', ['Akbar', 'Humayun', 'Babur', 'Jahangir'], 'C', 'Babur established Mughal rule in India after the First Battle of Panipat in 1526.'],
                    ['The Battle of Plassey was fought in which year?', ['1757', '1764', '1857', '1748'], 'A', 'The Battle of Plassey took place in 1757.'],
                    ['Who gave the slogan Do or Die during Quit India Movement?', ['Subhas Chandra Bose', 'Mahatma Gandhi', 'Jawaharlal Nehru', 'Bal Gangadhar Tilak'], 'B', 'Mahatma Gandhi gave the Do or Die call in 1942.'],
                ],
            ],
            [
                'Indian Geography',
                'Geography covers physical features, climate, rivers, soils, resources, agriculture and human geography of India.',
                'Use maps to revise states, rivers, mountain ranges, plateaus, coastal plains, climate regions and mineral belts.',
                ['The Himalayas are young fold mountains.', 'The Ganga-Brahmaputra system forms a large delta.', 'Monsoon rainfall is seasonal and varies regionally.'],
                [
                    ['Which is the longest river flowing entirely within India?', ['Ganga', 'Godavari', 'Narmada', 'Krishna'], 'B', 'The Godavari is the longest river flowing entirely within India.'],
                    ['The Thar Desert is mainly located in which state?', ['Gujarat', 'Punjab', 'Rajasthan', 'Haryana'], 'C', 'Most of the Thar Desert lies in Rajasthan.'],
                    ['Which soil is most suitable for cotton cultivation?', ['Black soil', 'Laterite soil', 'Mountain soil', 'Desert soil'], 'A', 'Black soil retains moisture and is suitable for cotton.'],
                    ['Which is the highest peak in India wholly within Indian territory?', ['Nanda Devi', 'K2', 'Anamudi', 'Kanchenjunga'], 'A', 'Nanda Devi is the highest peak wholly within India.'],
                    ['The Western Ghats run parallel to which coast?', ['Eastern coast', 'Western coast', 'Northern coast', 'None'], 'B', 'The Western Ghats run parallel to the western coast of India.'],
                ],
            ],
            [
                'Indian Polity',
                'Indian polity studies the Constitution, fundamental rights, Parliament, executive, judiciary and federal structure.',
                'Read the constitutional framework and understand the roles of Union and State institutions. Distinguish constitutional bodies from statutory bodies.',
                ['The Constitution came into force on 26 January 1950.', 'Fundamental Rights are contained in Part III.', 'The Supreme Court is the highest judicial body in India.'],
                [
                    ['When did the Constitution of India come into force?', ['15 August 1947', '26 January 1950', '26 November 1949', '2 October 1950'], 'B', 'The Constitution came into force on 26 January 1950.'],
                    ['Fundamental Rights are included in which Part?', ['Part II', 'Part III', 'Part IV', 'Part IVA'], 'B', 'Fundamental Rights are contained in Part III.'],
                    ['Who is the constitutional head of the Union Executive?', ['Prime Minister', 'President', 'Chief Justice', 'Speaker'], 'B', 'The President is the constitutional head of the Union Executive.'],
                    ['A Money Bill is defined under which Article?', ['Article 110', 'Article 112', 'Article 123', 'Article 356'], 'A', 'Article 110 defines a Money Bill.'],
                    ['Which house of Parliament is a permanent house?', ['Lok Sabha', 'Rajya Sabha', 'Both houses', 'Neither house'], 'B', 'Rajya Sabha is not subject to dissolution.'],
                ],
            ],
            [
                'Indian Economy',
                'Economics includes national income, inflation, banking, fiscal policy, monetary policy, employment and development.',
                'Understand basic economic indicators and the difference between fiscal policy and monetary policy. Practise simple percentage and growth calculations.',
                ['GDP measures the value of final goods and services produced.', 'The RBI is India\'s central bank.', 'Inflation is a sustained rise in the general price level.'],
                [
                    ['Which institution is the central bank of India?', ['SBI', 'RBI', 'SEBI', 'NABARD'], 'B', 'The Reserve Bank of India is the central bank.'],
                    ['GDP stands for what?', ['Gross Domestic Product', 'General Domestic Price', 'Gross Development Plan', 'Global Domestic Product'], 'A', 'GDP means Gross Domestic Product.'],
                    ['Inflation refers to a rise in which of the following?', ['General price level', 'Unemployment only', 'Exports only', 'Tax rates only'], 'A', 'Inflation is a sustained increase in the general price level.'],
                    ['Which tax is an example of an indirect tax in India?', ['Income tax', 'Corporate tax', 'GST', 'Wealth tax'], 'C', 'GST is an indirect tax levied on supply of goods and services.'],
                    ['Which body regulates the securities market in India?', ['RBI', 'SEBI', 'IRDAI', 'NITI Aayog'], 'B', 'SEBI regulates the securities market in India.'],
                ],
            ],
            [
                'General Science',
                'General science connects basic concepts from physics, chemistry, biology and environmental science.',
                'Build conceptual clarity before memorising facts. Relate scientific principles to everyday examples and practise units and definitions.',
                ['Science uses observation and experiments.', 'SI units provide a standard measurement system.', 'Scientific explanations should be supported by evidence.'],
                [
                    ['What is the SI unit of force?', ['Joule', 'Newton', 'Watt', 'Pascal'], 'B', 'The SI unit of force is the newton.'],
                    ['Which gas is essential for human respiration?', ['Nitrogen', 'Oxygen', 'Carbon dioxide', 'Hydrogen'], 'B', 'Oxygen is used in cellular respiration.'],
                    ['What is the pH of a neutral solution at 25 C?', ['0', '5', '7', '14'], 'C', 'A neutral aqueous solution has pH 7 at 25 C.'],
                    ['Which part of a plant conducts photosynthesis mainly?', ['Root', 'Leaf', 'Flower', 'Seed'], 'B', 'Leaves contain chlorophyll and are the main site of photosynthesis.'],
                    ['Which instrument measures atmospheric pressure?', ['Thermometer', 'Barometer', 'Hygrometer', 'Ammeter'], 'B', 'A barometer measures atmospheric pressure.'],
                ],
            ],
            [
                'Physics',
                'Physics studies matter, energy, motion, forces, heat, waves, electricity and magnetism.',
                'Revise definitions, SI units and core formulas. Practise numerical problems on motion, work, power, electricity and optics.',
                ['Speed = distance divided by time.', 'Work = force multiplied by displacement in the force direction.', 'Ohm\'s law: V = IR.'],
                [
                    ['What is the SI unit of power?', ['Joule', 'Watt', 'Newton', 'Volt'], 'B', 'Power is measured in watts.'],
                    ['Which law relates voltage, current and resistance?', ['Newton\'s law', 'Ohm\'s law', 'Faraday\'s law', 'Hooke\'s law'], 'B', 'Ohm\'s law states V = IR under constant physical conditions.'],
                    ['What is the approximate speed of light in vacuum?', ['3 x 10^6 m/s', '3 x 10^8 m/s', '3 x 10^4 m/s', '300 m/s'], 'B', 'The speed of light in vacuum is approximately 3 x 10^8 metres per second.'],
                    ['Which device converts electrical energy into mechanical energy?', ['Generator', 'Motor', 'Transformer', 'Cell'], 'B', 'An electric motor converts electrical energy into mechanical energy.'],
                    ['Which mirror is commonly used as a rear-view mirror?', ['Plane', 'Concave', 'Convex', 'Cylindrical'], 'C', 'A convex mirror gives a wider field of view.'],
                ],
            ],
            [
                'Chemistry',
                'Chemistry studies the composition, structure, properties and transformations of matter.',
                'Revise atoms, molecules, periodic trends, chemical bonding, acids and bases, metals and common reactions.',
                ['Atomic number equals the number of protons.', 'A solution has solute and solvent.', 'Acids turn blue litmus red.'],
                [
                    ['What is the chemical symbol for sodium?', ['So', 'Na', 'S', 'N'], 'B', 'The symbol Na comes from the Latin name natrium.'],
                    ['A solution with pH less than 7 is generally what?', ['Acidic', 'Basic', 'Neutral', 'Always saline'], 'A', 'A pH below 7 indicates an acidic solution at 25 C.'],
                    ['Which gas is released when an acid reacts with many metals?', ['Oxygen', 'Hydrogen', 'Nitrogen', 'Chlorine'], 'B', 'Many acid-metal reactions release hydrogen gas.'],
                    ['What is the formula of water?', ['CO2', 'H2O', 'O2', 'NaCl'], 'B', 'A water molecule contains two hydrogen atoms and one oxygen atom.'],
                    ['Which element is a noble gas?', ['Oxygen', 'Nitrogen', 'Neon', 'Chlorine'], 'C', 'Neon is a noble gas in Group 18.'],
                ],
            ],
            [
                'Biology',
                'Biology covers living organisms, cells, human physiology, genetics, plant systems and ecology.',
                'Learn cell structures, organ systems, nutrition, respiration, reproduction and basic genetics with labelled diagrams.',
                ['The cell is the basic structural and functional unit of life.', 'DNA carries hereditary information.', 'Photosynthesis converts light energy into chemical energy.'],
                [
                    ['What is the basic unit of life?', ['Tissue', 'Organ', 'Cell', 'Organ system'], 'C', 'The cell is the basic structural and functional unit of life.'],
                    ['Which organ pumps blood through the human body?', ['Lungs', 'Heart', 'Kidney', 'Liver'], 'B', 'The heart pumps blood through the circulatory system.'],
                    ['Which molecule carries hereditary information?', ['DNA', 'Glucose', 'Starch', 'Water'], 'A', 'DNA stores genetic information in most organisms.'],
                    ['Where does photosynthesis mainly occur in plant cells?', ['Mitochondria', 'Chloroplasts', 'Nucleus', 'Ribosomes'], 'B', 'Chloroplasts contain chlorophyll and are the main site of photosynthesis.'],
                    ['Which blood cells help in clotting?', ['Red blood cells', 'White blood cells', 'Platelets', 'Plasma only'], 'C', 'Platelets help in blood clotting.'],
                ],
            ],
            [
                'Mathematics',
                'Mathematics for competitive exams includes arithmetic, algebra, geometry, mensuration, statistics and data interpretation.',
                'Practise calculations daily. Memorise essential formulas and solve questions with both accuracy and time management.',
                ['Percentage = (part / whole) x 100.', 'Simple interest = (Principal x Rate x Time) / 100.', 'Area of a rectangle = length x breadth.'],
                [
                    ['What is 25 percent of 240?', ['40', '50', '60', '80'], 'C', '25 percent is one quarter; 240 divided by 4 equals 60.'],
                    ['What is the square of 15?', ['125', '200', '225', '250'], 'C', '15 multiplied by 15 equals 225.'],
                    ['The average of 10, 20 and 30 is what?', ['15', '20', '25', '30'], 'B', 'The sum is 60; dividing by 3 gives 20.'],
                    ['What is the simple interest on Rs 1000 at 10 percent per year for 2 years?', ['100', '150', '200', '250'], 'C', 'SI = 1000 x 10 x 2 / 100 = Rs 200.'],
                    ['If a ratio is 2:3 and the total is 25, what is the first share?', ['5', '10', '15', '20'], 'B', 'There are 5 total parts; each part is 5, so the first share is 10.'],
                ],
            ],
            [
                'Reasoning',
                'Reasoning develops logical thinking through series, analogy, classification, coding-decoding and relationships.',
                'Identify the rule before selecting an answer. For series, compare differences, ratios and alternating patterns.',
                ['Check common properties in analogy questions.', 'For number series, test differences and multiplication patterns.', 'Draw a small diagram for direction and relation problems.'],
                [
                    ['Find the next number: 2, 4, 8, 16, ?', ['20', '24', '30', '32'], 'D', 'Each term is multiplied by 2; the next number is 32.'],
                    ['Book is to Reading as Fork is to what?', ['Writing', 'Eating', 'Drawing', 'Running'], 'B', 'A book is used for reading and a fork is used for eating.'],
                    ['Which one is different?', ['Square', 'Triangle', 'Circle', 'Cube'], 'D', 'Cube is three-dimensional; the others are plane figures.'],
                    ['If CAT is coded as DBU, how is DOG coded?', ['EPH', 'CNG', 'FQI', 'EOG'], 'A', 'Each letter is shifted forward by one position.'],
                    ['A person faces north and turns right. Which direction is faced now?', ['West', 'South', 'East', 'North'], 'C', 'A right turn from north points east.'],
                ],
            ],
            [
                'English',
                'English language preparation covers grammar, vocabulary, comprehension, sentence correction and usage.',
                'Read short passages daily. Revise parts of speech, subject-verb agreement, tenses, articles, prepositions and common vocabulary.',
                ['A noun names a person, place, thing or idea.', 'A verb expresses an action or state.', 'Subject and verb should agree in number and person.'],
                [
                    ['Choose the correct plural of child.', ['Childs', 'Children', 'Childes', 'Childrens'], 'B', 'Children is the irregular plural of child.'],
                    ['Choose the synonym of rapid.', ['Slow', 'Quick', 'Weak', 'Late'], 'B', 'Rapid means fast or quick.'],
                    ['Select the correct sentence.', ['She go to school.', 'She goes to school.', 'She going school.', 'She gone school.'], 'B', 'The singular subject she takes the verb goes in the simple present tense.'],
                    ['What is the antonym of ancient?', ['Old', 'Historic', 'Modern', 'Past'], 'C', 'Modern is the opposite of ancient.'],
                    ['Identify the adjective: The tall building is visible.', ['Building', 'Visible', 'Tall', 'Is'], 'C', 'Tall describes the noun building.'],
                ],
            ],
        ];
    }
}