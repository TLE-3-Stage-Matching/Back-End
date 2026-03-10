<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $softwareEngineeringTags = [
            // Programming Languages
            ['name' => 'PHP', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'JavaScript', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'TypeScript', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Python', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Java', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'C#', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'C++', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Go', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Rust', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Ruby', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Swift', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Kotlin', 'tag_type' => 'skill', 'is_active' => true],

            // Frontend Frameworks
            ['name' => 'React', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Vue.js', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Angular', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Svelte', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Next.js', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Nuxt.js', 'tag_type' => 'skill', 'is_active' => true],

            // Backend Frameworks
            ['name' => 'Laravel', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Node.js', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Express.js', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Django', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Flask', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Spring Boot', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'ASP.NET', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Ruby on Rails', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'FastAPI', 'tag_type' => 'skill', 'is_active' => true],

            // Databases
            ['name' => 'MySQL', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'PostgreSQL', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'MongoDB', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Redis', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'SQLite', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'SQL Server', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Oracle', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Elasticsearch', 'tag_type' => 'skill', 'is_active' => true],

            // DevOps & Cloud
            ['name' => 'Docker', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Kubernetes', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'AWS', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Azure', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Google Cloud', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'CI/CD', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Jenkins', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'GitHub Actions', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Terraform', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Ansible', 'tag_type' => 'skill', 'is_active' => true],

            // Version Control
            ['name' => 'Git', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'GitHub', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'GitLab', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Bitbucket', 'tag_type' => 'skill', 'is_active' => true],

            // Mobile Development
            ['name' => 'React Native', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Flutter', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'iOS Development', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Android Development', 'tag_type' => 'skill', 'is_active' => true],

            // Testing
            ['name' => 'Unit Testing', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Integration Testing', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Jest', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'PHPUnit', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Selenium', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Cypress', 'tag_type' => 'skill', 'is_active' => true],

            // Methodologies & Practices
            ['name' => 'Agile', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Scrum', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'REST API', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'GraphQL', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Microservices', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'TDD', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Clean Code', 'tag_type' => 'skill', 'is_active' => true],

            // AI & Data Science
            ['name' => 'Machine Learning', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Deep Learning', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Data Science', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'TensorFlow', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'PyTorch', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Natural Language Processing', 'tag_type' => 'skill', 'is_active' => true],

            // Web Technologies
            ['name' => 'HTML', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'CSS', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'SASS/SCSS', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Tailwind CSS', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Bootstrap', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Webpack', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Vite', 'tag_type' => 'skill', 'is_active' => true],

            // Security
            ['name' => 'Cybersecurity', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'OAuth', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'JWT', 'tag_type' => 'skill', 'is_active' => true],
            ['name' => 'Penetration Testing', 'tag_type' => 'skill', 'is_active' => true],

            // Industry/Role Types
            ['name' => 'Software Development', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Web Development', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Mobile Development', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'DevOps', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Data Engineering', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Cloud Engineering', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Full Stack Development', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Frontend Development', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Backend Development', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'QA/Testing', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'IT Support', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'Network Engineering', 'tag_type' => 'industry', 'is_active' => true],
            ['name' => 'System Administration', 'tag_type' => 'industry', 'is_active' => true],

            // Education Levels / Majors
            ['name' => 'Computer Science', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Software Engineering', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Information Technology', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Information Systems', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Computer Engineering', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Electrical Engineering', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Data Science', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Cybersecurity', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Artificial Intelligence', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Network Administration', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Web Development', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Game Development', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Database Administration', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Business Informatics', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Applied Computing', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Digital Media', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Interactive Media', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Mechatronics', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Robotics', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Mathematics', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Statistics', 'tag_type' => 'major', 'is_active' => true],
            ['name' => 'Physics', 'tag_type' => 'major', 'is_active' => true],

            // Personality Traits
            ['name' => 'Creative', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Social', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Leadership', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Analytical', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Detail-oriented', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Problem Solver', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Team Player', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Independent', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Self-motivated', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Adaptable', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Communicative', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Organized', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Curious', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Innovative', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Patient', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Persistent', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Proactive', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Reliable', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Resourceful', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Strategic Thinker', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Critical Thinker', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Fast Learner', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Collaborative', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Empathetic', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Enthusiastic', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Flexible', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Goal-oriented', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Humble', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Open-minded', 'tag_type' => 'trait', 'is_active' => true],
            ['name' => 'Positive Attitude', 'tag_type' => 'trait', 'is_active' => true],
        ];

        foreach ($softwareEngineeringTags as $tag) {
            Tag::firstOrCreate(
                ['name' => $tag['name']],
                $tag
            );
        }
    }
}

