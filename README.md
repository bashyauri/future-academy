# Future Academy - Learning Management System

A comprehensive Learning Management System (LMS) built with Laravel 11, Livewire 3, and FilamentPHP 3 for JAMB, WAEC, and NECO exam preparation.

## Features

### Student Features
- **Onboarding Flow**: Select stream (Science, Arts, Social Sciences) or choose subjects manually
- **Exam Type Selection**: Choose between JAMB, WAEC, NECO, or combination
- **Subject Selection**: Pick subjects based on stream or custom selection
- **Video Lessons**: Watch educational videos with progress tracking
- **Question Bank**: Access past questions from WAEC, NECO, JAMB (5 years each) and FA custom questions
- **Mock Exams**: Take timed mock exams with:
  - Countdown timer
  - Auto-submit on timeout
  - Results shown after submission
  - Anti-cheat measures
- **Progress Tracking**: Monitor video completion and question attempts

### Parent Features
- **Child Enrollment**: Link and enroll multiple children
- **Progress Monitoring**: View children's:
  - Video watch time and completion
  - Question attempts and scores
  - Mock exam performance
  - Overall progress

### Teacher Features
- **Content Management**:
  - Upload video lessons
  - Create and upload questions (WAEC, NECO, JAMB, FA)
  - CSV/JSON import for bulk question upload
  - Create mock exams
- **Student Monitoring**:
  - View student progress
  - Track student performance
  - Enroll students in subjects

### Admin Features
- Full system management through Filament admin panel
- User management
- Content approval
- System settings

## Tech Stack

- **Laravel 11**: Backend framework
- **Livewire 3**: Frontend reactivity
- **FilamentPHP 3**: Admin panels and resources
- **TailwindCSS 3**: Styling
- **Alpine.js 3**: Minimal JavaScript interactions
- **Spatie Laravel Permission**: Role-based access control
- **Maatwebsite Excel**: CSV/Excel import/export

## Installation

### Prerequisites
- PHP 8.3+
- Composer
- Node.js & NPM
- MySQL 5.7+ or PostgreSQL
- Git

### Setup Steps

1. **Clone the repository**
```bash
git clone <repository-url>
cd future-academy
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install NPM dependencies**
```bash
npm install
```

4. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configure database**
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=future_academy
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. **Run migrations and seeders**
```bash
php artisan migrate:fresh --seed
```

This will create:
- Database tables
- Roles and permissions
- Sample streams (Science, Arts, Social Sciences)
- Exam types (JAMB, WAEC, NECO)
- Subjects with icons and colors
- Sample users

7. **Build assets**
```bash
npm run build
# or for development
npm run dev
```

8. **Start the development server**
```bash
php artisan serve
```

Visit `http://localhost:8000`

## Default Users

After seeding, you'll have these default users:

- **Admin**: Check UserSeeder for credentials
- **Teacher**: Check UserSeeder for credentials
- **Student**: Check UserSeeder for credentials
- **Parent**: Check UserSeeder for credentials

## File Structure

### Key Directories

```
app/
├── Livewire/
│   ├── Onboarding/
│   │   └── StudentOnboarding.php    # Student onboarding flow
│   ├── Home/
│   │   └── HomePage.php               # Landing page
│   └── Quizzes/                       # Mock exam components
├── Models/
│   ├── User.php                       # Enhanced with relationships
│   ├── Stream.php                     # Science/Arts/Social Sciences
│   ├── Subject.php                    # Subjects
│   ├── ExamType.php                   # JAMB/WAEC/NECO
│   ├── Question.php                   # Question bank
│   ├── Quiz.php                       # Mock exams
│   ├── Enrollment.php                 # Student-subject enrollment
│   └── ...
├── Filament/
│   └── Resources/                     # Admin panel resources
└── Imports/
    └── QuestionsImport.php            # CSV/JSON question import

database/
├── migrations/
│   ├── 2025_12_08_000001_add_onboarding_fields_to_users_table.php
│   ├── 2025_12_08_000002_create_streams_table.php
│   ├── 2025_12_08_000003_create_parent_student_table.php
│   └── 2025_12_08_000004_create_enrollments_table.php
└── seeders/
    ├── StreamSeeder.php               # Seeds streams
    ├── ExamTypeSeeder.php             # Seeds exam types
    └── SubjectSeeder.php              # Seeds subjects

resources/
└── views/
    └── livewire/
        ├── onboarding/
        │   └── student-onboarding.blade.php
        └── home/
            └── home-page.blade.php
```

## User Flows

### Student Flow
1. **Registration** → Email verification
2. **First Login** → Onboarding:
   - Step 1: Choose stream (Science/Arts/Social Sciences) or custom
   - Step 2: Select exam types (JAMB/WAEC/NECO)
   - Step 3: Choose subjects
3. **Dashboard** → Access to:
   - Video lessons
   - Question bank
   - Mock exams
   - Progress tracking

### Parent Flow
1. **Registration** → Email verification
2. **Dashboard** → Parent panel:
   - Add/link children
   - View children's progress
   - Monitor performance

### Teacher Flow
1. **Registration** → Admin approval
2. **Dashboard** → Teacher panel:
   - Upload videos
   - Create/import questions
   - Create mock exams
   - View student progress

## Key Features Implementation

### Onboarding System
Located in: `app/Livewire/Onboarding/StudentOnboarding.php`

Features:
- Multi-step wizard (3 steps)
- Progress bar
- Stream selection with default subjects
- Custom subject selection
- Exam type multi-select
- Automatic enrollment creation

### Home Page
Located in: `app/Livewire/Home/HomePage.php`

Features:
- Three access cards (Student, Teacher, Parent)
- Feature highlights
- Responsive design
- Role-specific login links

### Database Schema

Key relationships:
- Users have many enrollments
- Users belong to many subjects through enrollments
- Parents belong to many children (students) through parent_student pivot
- Questions belong to subjects and exam types
- Quizzes have many questions
- Users have many quiz attempts

## Routes

```php
// Public routes
Route::get('/', HomePage::class)->name('home');
Route::get('/login', ...)->name('login');

// Student routes (after auth)
Route::middleware(['auth', 'student'])->group(function () {
    Route::get('/onboarding', StudentOnboarding::class)->name('onboarding');
    Route::get('/dashboard', ...)->name('student.dashboard');
});

// Parent routes
Route::middleware(['auth', 'parent'])->group(function () {
    // Parent panel routes
});

// Teacher routes
Route::middleware(['auth', 'teacher'])->group(function () {
    // Teacher panel routes
});
```

## Filament Panels

The system uses multiple Filament panels:

1. **Admin Panel** (`/admin`) - For super-admin and admin
2. **Teacher Panel** (`/teacher`) - For teachers
3. **Parent Panel** (`/parent`) - For parents
4. **Student Panel** - Can be hybrid Filament + custom Livewire

## Question Import

Teachers can import questions via CSV/JSON:

### CSV Format
```csv
subject_code,body,option_a,option_b,option_c,option_d,correct_answer,exam_type,year
MATH,"What is 2+2?","2","3","4","5","C","jamb","2024"
```

### JSON Format
```json
[
  {
    "subject_code": "MATH",
    "body": "What is 2+2?",
    "options": [
      {"label": "A", "text": "2", "is_correct": false},
      {"label": "B", "text": "3", "is_correct": false},
      {"label": "C", "text": "4", "is_correct": true},
      {"label": "D", "text": "5", "is_correct": false}
    ],
    "exam_type": "jamb",
    "year": "2024"
  }
]
```

## Mock Exam System

Features:
- Timed exams with countdown
- Server-side time enforcement
- Auto-submit on timeout
- Tab-switch detection (Alpine.js)
- Results only after submission
- Detailed performance analytics

## Email Verification

Email verification is required before access. Configure in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@futureacademy.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Development

### Running tests
```bash
php artisan test
```

### Code formatting
```bash
./vendor/bin/pint
```

### Clear cache
```bash
php artisan optimize:clear
```

## Production Deployment

1. Set environment to production:
```env
APP_ENV=production
APP_DEBUG=false
```

2. Optimize for production:
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

3. Set proper permissions:
```bash
chmod -R 755 storage bootstrap/cache
```

## Troubleshooting

### Migration issues
```bash
php artisan migrate:fresh --seed
```

### Permission issues
```bash
php artisan permission:cache-reset
```

### Filament issues
```bash
php artisan filament:upgrade
php artisan filament:cache-components
```

## Support

For issues or questions, please contact support or create an issue in the repository.

## License

This project is proprietary and confidential.

---

**Future Academy** - Empowering students for exam success! 🎓
