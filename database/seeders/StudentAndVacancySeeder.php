<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyUser;
use App\Models\StudentPreference;
use App\Models\StudentProfile;
use App\Models\StudentTag;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyRequirement;
use Illuminate\Database\Seeder;

/**
 * Seeds students and vacancies with overlapping tags so that the
 * student–vacancy tag matching (cosine similarity) produces meaningful
 * match scores for testing and AI match generation.
 *
 * Design:
 * - Several companies with locations and company users.
 * - Vacancies with requirements: max per type (skill 6, industry 5, trait 4, major 5).
 * - Students with profiles and student tags: max per type (skill 6, trait 4, industry 5, major 1).
 * - Deliberate overlap: some students share many tags with specific
 *   vacancies (high match), others partial (medium) or few (low).
 */
class StudentAndVacancySeeder extends Seeder
{
    private const MAX_SKILLS_VACANCY = 6;
    private const MAX_INDUSTRY_VACANCY = 5;
    private const MAX_TRAIT_VACANCY = 4;
    private const MAX_MAJOR_VACANCY = 5;
    private const MAX_SKILLS_STUDENT = 6;
    private const MAX_TRAIT_STUDENT = 4;
    private const MAX_INDUSTRY_STUDENT = 5;
    private const MAX_MAJOR_STUDENT = 1;
    public function run(): void
    {
        $tagIds = Tag::all()->keyBy('name');

        if ($tagIds->isEmpty()) {
            $this->command->warn('No tags found. Run TagSeeder first.');
            return;
        }

        $companies = $this->createCompanies($tagIds);
        $vacancies = $this->createVacancies($companies, $tagIds);
        $this->createStudents($tagIds, $vacancies);
    }

    private function createCompanies(\Illuminate\Support\Collection $tagIds): array
    {
        $industryTagId = $tagIds->get('Software Development')?->id ?? $tagIds->first()->id;

        $companies = [];

        $companies[] = Company::firstOrCreate(
            ['email' => 'contact@techcorp.nl'],
            [
                'name' => 'TechCorp Nederland',
                'industry_tag_id' => $industryTagId,
                'phone' => '+31201234567',
                'size_category' => 'medium',
                'description' => 'Software development and cloud solutions.',
                'is_active' => true,
            ]
        );
        CompanyLocation::firstOrCreate(
            ['company_id' => $companies[0]->id, 'city' => 'Amsterdam'],
            [
                'address_line' => 'Keizersgracht 123',
                'postal_code' => '1015 CJ',
                'country' => 'NL',
                'is_primary' => true,
            ]
        );
        $user1 = User::firstOrCreate(
            ['email' => 'hr@techcorp.nl'],
            [
                'role' => UserRole::Company,
                'password_hash' => 'secret',
                'first_name' => 'Jan',
                'last_name' => 'de Vries',
            ]
        );
        CompanyUser::firstOrCreate(
            ['user_id' => $user1->id],
            ['company_id' => $companies[0]->id, 'job_title' => 'HR Manager']
        );

        $companies[] = Company::firstOrCreate(
            ['email' => 'info@dataflow.nl'],
            [
                'name' => 'DataFlow BV',
                'industry_tag_id' => $tagIds->get('Data Engineering')?->id ?? $industryTagId,
                'phone' => '+31612345678',
                'size_category' => 'small',
                'description' => 'Data engineering and ML platforms.',
                'is_active' => true,
            ]
        );
        CompanyLocation::firstOrCreate(
            ['company_id' => $companies[1]->id, 'city' => 'Rotterdam'],
            [
                'address_line' => 'Hoofdstraat 45',
                'postal_code' => '3011 AA',
                'country' => 'NL',
                'is_primary' => true,
            ]
        );
        $user2 = User::firstOrCreate(
            ['email' => 'jobs@dataflow.nl'],
            [
                'role' => UserRole::Company,
                'password_hash' => 'secret',
                'first_name' => 'Lisa',
                'last_name' => 'Jansen',
            ]
        );
        CompanyUser::firstOrCreate(
            ['user_id' => $user2->id],
            ['company_id' => $companies[1]->id, 'job_title' => 'Recruiter']
        );

        $companies[] = Company::firstOrCreate(
            ['email' => 'hello@frontendstudio.nl'],
            [
                'name' => 'Frontend Studio',
                'industry_tag_id' => $tagIds->get('Frontend Development')?->id ?? $industryTagId,
                'size_category' => 'small',
                'description' => 'Frontend and UX focus.',
                'is_active' => true,
            ]
        );
        CompanyLocation::firstOrCreate(
            ['company_id' => $companies[2]->id, 'city' => 'Utrecht'],
            [
                'address_line' => 'Utrechtseweg 78',
                'postal_code' => '3524 AB',
                'country' => 'NL',
                'is_primary' => true,
            ]
        );
        $user3 = User::firstOrCreate(
            ['email' => 'team@frontendstudio.nl'],
            [
                'role' => UserRole::Company,
                'password_hash' => 'secret',
                'first_name' => 'Tom',
                'last_name' => 'Bakker',
            ]
        );
        CompanyUser::firstOrCreate(
            ['user_id' => $user3->id],
            ['company_id' => $companies[2]->id, 'job_title' => 'Lead Developer']
        );

        return $companies;
    }

    private function createVacancies(array $companies, \Illuminate\Support\Collection $tagIds): array
    {
        $vacancies = [];

        $v1 = Vacancy::firstOrCreate(
            ['company_id' => $companies[0]->id, 'title' => 'Backend Developer PHP/Laravel'],
            [
                'location_id' => $companies[0]->locations()->first()->id,
                'hours_per_week' => 40,
                'description' => 'Backend development with Laravel and PHP.',
                'status' => 'open',
            ]
        );
        $this->addRequirementsByNames($v1, $tagIds, $this->vacancyRequirementsV1());
        $vacancies[] = $v1;

        $v2 = Vacancy::firstOrCreate(
            ['company_id' => $companies[0]->id, 'title' => 'Full Stack Developer React/Node'],
            [
                'location_id' => $companies[0]->locations()->first()->id,
                'hours_per_week' => 32,
                'description' => 'Full stack with React and Node.js.',
                'status' => 'open',
            ]
        );
        $this->addRequirementsByNames($v2, $tagIds, $this->vacancyRequirementsV2());
        $vacancies[] = $v2;

        $v3 = Vacancy::firstOrCreate(
            ['company_id' => $companies[1]->id, 'title' => 'Data Engineering Intern'],
            [
                'location_id' => $companies[1]->locations()->first()->id,
                'hours_per_week' => 24,
                'description' => 'Data pipelines and Python.',
                'status' => 'open',
            ]
        );
        $this->addRequirementsByNames($v3, $tagIds, $this->vacancyRequirementsV3());
        $vacancies[] = $v3;

        $v4 = Vacancy::firstOrCreate(
            ['company_id' => $companies[2]->id, 'title' => 'Frontend Developer React/Vue'],
            [
                'location_id' => $companies[2]->locations()->first()->id,
                'hours_per_week' => 40,
                'description' => 'Frontend with React or Vue.',
                'status' => 'open',
            ]
        );
        $this->addRequirementsByNames($v4, $tagIds, $this->vacancyRequirementsV4());
        $vacancies[] = $v4;

        $v5 = Vacancy::firstOrCreate(
            ['company_id' => $companies[0]->id, 'title' => 'DevOps Intern'],
            [
                'location_id' => $companies[0]->locations()->first()->id,
                'hours_per_week' => 32,
                'description' => 'Docker, CI/CD, cloud.',
                'status' => 'open',
            ]
        );
        $this->addRequirementsByNames($v5, $tagIds, $this->vacancyRequirementsV5());
        $vacancies[] = $v5;

        return $vacancies;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int}>
     */
    private function vacancyRequirementsV1(): array
    {
        $skills = [
            ['PHP', 'skill', 10], ['Laravel', 'skill', 10], ['MySQL', 'skill', 8], ['REST API', 'skill', 8],
            ['JavaScript', 'skill', 6], ['TypeScript', 'skill', 6],
        ];
        $industry = [
            ['Backend Development', 'industry', 9], ['Software Development', 'industry', 8], ['Web Development', 'industry', 7],
            ['DevOps', 'industry', 5], ['Data Engineering', 'industry', 5],
        ];
        $traits = [
            ['Team Player', 'trait', 8], ['Problem Solver', 'trait', 7], ['Analytical', 'trait', 7], ['Detail-oriented', 'trait', 6],
        ];
        $majors = [
            ['Computer Science', 'major', 9], ['Software Engineering', 'major', 8], ['Information Technology', 'major', 7],
            ['Information Systems', 'major', 6], ['Computer Engineering', 'major', 6],
        ];
        return array_merge($skills, $industry, $traits, $majors);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int}>
     */
    private function vacancyRequirementsV2(): array
    {
        $skills = [
            ['React', 'skill', 10], ['Node.js', 'skill', 10], ['TypeScript', 'skill', 9], ['JavaScript', 'skill', 9],
            ['Vue.js', 'skill', 6], ['HTML', 'skill', 7],
        ];
        $industry = [
            ['Full Stack Development', 'industry', 9], ['Frontend Development', 'industry', 8], ['Software Development', 'industry', 7],
            ['Web Development', 'industry', 7], ['Backend Development', 'industry', 6],
        ];
        $traits = [
            ['Problem Solver', 'trait', 8], ['Team Player', 'trait', 7], ['Creative', 'trait', 6], ['Adaptable', 'trait', 7],
        ];
        $majors = [
            ['Computer Science', 'major', 9], ['Software Engineering', 'major', 8], ['Information Technology', 'major', 7],
            ['Electrical Engineering', 'major', 6], ['Computer Engineering', 'major', 6],
        ];
        return array_merge($skills, $industry, $traits, $majors);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int}>
     */
    private function vacancyRequirementsV3(): array
    {
        $skills = [
            ['Python', 'skill', 10], ['Data Science', 'skill', 9], ['Machine Learning', 'skill', 7], ['MySQL', 'skill', 6],
            ['PostgreSQL', 'skill', 6], ['Docker', 'skill', 6],
        ];
        $industry = [
            ['Data Engineering', 'industry', 9], ['Software Development', 'industry', 7], ['Cloud Engineering', 'industry', 7],
            ['DevOps', 'industry', 6], ['Backend Development', 'industry', 6],
        ];
        $traits = [
            ['Analytical', 'trait', 9], ['Detail-oriented', 'trait', 8], ['Problem Solver', 'trait', 8], ['Curious', 'trait', 7],
        ];
        $majors = [
            ['Information Technology', 'major', 8], ['Computer Science', 'major', 8], ['Artificial Intelligence', 'major', 7],
            ['Mathematics', 'major', 6], ['Statistics', 'major', 6],
        ];
        return array_merge($skills, $industry, $traits, $majors);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int}>
     */
    private function vacancyRequirementsV4(): array
    {
        $skills = [
            ['React', 'skill', 10], ['Vue.js', 'skill', 9], ['JavaScript', 'skill', 9], ['TypeScript', 'skill', 8],
            ['HTML', 'skill', 8], ['CSS', 'skill', 8],
        ];
        $industry = [
            ['Frontend Development', 'industry', 9], ['Web Development', 'industry', 8], ['Software Development', 'industry', 7],
            ['Full Stack Development', 'industry', 6], ['Mobile Development', 'industry', 5],
        ];
        $traits = [
            ['Creative', 'trait', 8], ['Detail-oriented', 'trait', 7], ['Team Player', 'trait', 7], ['Adaptable', 'trait', 7],
        ];
        $majors = [
            ['Computer Science', 'major', 8], ['Game Development', 'major', 7], ['Software Engineering', 'major', 7],
            ['Information Technology', 'major', 6], ['Digital Media', 'major', 5],
        ];
        return array_merge($skills, $industry, $traits, $majors);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int}>
     */
    private function vacancyRequirementsV5(): array
    {
        $skills = [
            ['Docker', 'skill', 10], ['CI/CD', 'skill', 9], ['AWS', 'skill', 8], ['Kubernetes', 'skill', 7],
            ['Terraform', 'skill', 7], ['Git', 'skill', 8],
        ];
        $industry = [
            ['DevOps', 'industry', 9], ['Cloud Engineering', 'industry', 8], ['Software Development', 'industry', 7],
            ['Data Engineering', 'industry', 5], ['Backend Development', 'industry', 6],
        ];
        $traits = [
            ['Adaptable', 'trait', 8], ['Problem Solver', 'trait', 8], ['Detail-oriented', 'trait', 7], ['Proactive', 'trait', 7],
        ];
        $majors = [
            ['Software Engineering', 'major', 8], ['Computer Science', 'major', 8], ['Information Technology', 'major', 7],
            ['Computer Engineering', 'major', 6], ['Network Administration', 'major', 5],
        ];
        return array_merge($skills, $industry, $traits, $majors);
    }

    private function addRequirement(Vacancy $vacancy, int $tagId, string $requirementType, int $importance): void
    {
        VacancyRequirement::firstOrCreate(
            [
                'vacancy_id' => $vacancy->id,
                'tag_id' => $tagId,
                'requirement_type' => $requirementType,
            ],
            ['importance' => $importance]
        );
    }

    /**
     * Add vacancy requirements from array of [tagName, requirementType, importance].
     * Skips missing tags. Used to fill up to max per type.
     *
     * @param  array<int, array{0: string, 1: string, 2: int}>  $entries
     */
    private function addRequirementsByNames(Vacancy $vacancy, \Illuminate\Support\Collection $tagIds, array $entries): void
    {
        foreach ($entries as [$name, $requirementType, $importance]) {
            $tag = $tagIds->get($name);
            if ($tag) {
                $this->addRequirement($vacancy, $tag->id, $requirementType, $importance);
            }
        }
    }

    private function createStudents(\Illuminate\Support\Collection $tagIds, array $vacancies): void
    {
        $desiredRoleTagId = $tagIds->get('Backend Development')?->id ?? $tagIds->get('Software Development')?->id;

        $computerScience = $tagIds->get('Computer Science');
        $softwareEngineering = $tagIds->get('Software Engineering');
        $informationTechnology = $tagIds->get('Information Technology');

        $s1 = $this->createStudent('Anna', 'Visser', 'anna.visser@student.nl');
        $this->attachStudentTags($s1, $tagIds, $this->studentTagPairsV1());
        $this->attachStudentMajor($s1, $computerScience);
        $this->maybePreference($s1, $desiredRoleTagId);

        $s2 = $this->createStudent('Bram', 'Smit', 'bram.smit@student.nl');
        $this->attachStudentTags($s2, $tagIds, $this->studentTagPairsV2());
        $this->attachStudentMajor($s2, $computerScience);
        $this->maybePreference($s2, $tagIds->get('Full Stack Development')?->id);

        $s3 = $this->createStudent('Clara', 'de Jong', 'clara.jong@student.nl');
        $this->attachStudentTags($s3, $tagIds, $this->studentTagPairsV3());
        $this->attachStudentMajor($s3, $informationTechnology);
        $this->maybePreference($s3, $tagIds->get('Data Engineering')?->id);

        $s4 = $this->createStudent('Daan', 'Mulder', 'daan.mulder@student.nl');
        $this->attachStudentTags($s4, $tagIds, $this->studentTagPairsV4());
        $this->attachStudentMajor($s4, $computerScience);
        $this->maybePreference($s4, $tagIds->get('Frontend Development')?->id);

        $s5 = $this->createStudent('Eva', 'Bos', 'eva.bos@student.nl');
        $this->attachStudentTags($s5, $tagIds, $this->studentTagPairsV5());
        $this->attachStudentMajor($s5, $softwareEngineering);
        $this->maybePreference($s5, $tagIds->get('DevOps')?->id);

        $s6 = $this->createStudent('Finn', 'Vermeulen', 'finn.vermeulen@student.nl');
        $this->attachStudentTags($s6, $tagIds, $this->studentTagPairsV6());
        $this->attachStudentMajor($s6, $computerScience);

        $s7 = $this->createStudent('Gijs', 'Peters', 'gijs.peters@student.nl');
        $this->attachStudentTags($s7, $tagIds, $this->studentTagPairsV7());
        $this->attachStudentMajor($s7, $informationTechnology);

        $s8 = $this->createStudent('Hannah', 'van Dijk', 'hannah.dijk@student.nl');
        $this->attachStudentTags($s8, $tagIds, $this->studentTagPairsV8());
        $this->attachStudentMajor($s8, $computerScience);

        $s9 = $this->createStudent('Isa', 'de Boer', 'isa.boer@student.nl');
        $this->attachStudentTags($s9, $tagIds, $this->studentTagPairsV9());
        $this->attachStudentMajor($s9, $softwareEngineering);

        $s10 = $this->createStudent('Jasper', 'Jansen', 'jasper.jansen@student.nl');
        $this->attachStudentTags($s10, $tagIds, $this->studentTagPairsV10());
        $this->attachStudentMajor($s10, $computerScience);
    }

    /**
     * Student tag pairs [name, weight] – max 6 skills, 5 industry, 4 traits per student (1 major added separately).
     *
     * @return array<int, array{0: string, 1: int}>
     */
    private function studentTagPairsV1(): array
    {
        $skills = [['PHP', 10], ['Laravel', 9], ['MySQL', 8], ['REST API', 8], ['JavaScript', 6], ['TypeScript', 6]];
        $industry = [['Backend Development', 8], ['Software Development', 7], ['Web Development', 6], ['DevOps', 5], ['Data Engineering', 5]];
        $traits = [['Team Player', 8], ['Problem Solver', 7], ['Analytical', 7], ['Detail-oriented', 6]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV2(): array
    {
        $skills = [['React', 10], ['Node.js', 9], ['TypeScript', 9], ['JavaScript', 9], ['Vue.js', 6], ['HTML', 7]];
        $industry = [['Full Stack Development', 9], ['Frontend Development', 8], ['Software Development', 7], ['Web Development', 7], ['Backend Development', 6]];
        $traits = [['Problem Solver', 8], ['Team Player', 7], ['Creative', 6], ['Adaptable', 7]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV3(): array
    {
        $skills = [['Python', 10], ['Data Science', 9], ['Machine Learning', 7], ['MySQL', 6], ['PostgreSQL', 6], ['Docker', 6]];
        $industry = [['Data Engineering', 9], ['Software Development', 7], ['Cloud Engineering', 7], ['DevOps', 6], ['Backend Development', 6]];
        $traits = [['Analytical', 9], ['Detail-oriented', 8], ['Problem Solver', 8], ['Curious', 7]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV4(): array
    {
        $skills = [['React', 10], ['Vue.js', 9], ['JavaScript', 9], ['TypeScript', 8], ['HTML', 8], ['CSS', 8]];
        $industry = [['Frontend Development', 9], ['Web Development', 8], ['Software Development', 7], ['Full Stack Development', 6], ['Mobile Development', 5]];
        $traits = [['Creative', 8], ['Detail-oriented', 7], ['Team Player', 7], ['Adaptable', 7]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV5(): array
    {
        $skills = [['Docker', 10], ['CI/CD', 9], ['AWS', 8], ['Kubernetes', 7], ['Terraform', 7], ['Git', 8]];
        $industry = [['DevOps', 9], ['Cloud Engineering', 8], ['Software Development', 7], ['Data Engineering', 5], ['Backend Development', 6]];
        $traits = [['Adaptable', 8], ['Problem Solver', 8], ['Detail-oriented', 7], ['Proactive', 7]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV6(): array
    {
        $skills = [['PHP', 7], ['React', 8], ['JavaScript', 8], ['Laravel', 6], ['Node.js', 6], ['TypeScript', 6]];
        $industry = [['Backend Development', 7], ['Full Stack Development', 7], ['Software Development', 6], ['Web Development', 6], ['Frontend Development', 5]];
        $traits = [['Team Player', 7], ['Problem Solver', 7], ['Adaptable', 6], ['Fast Learner', 6]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV7(): array
    {
        $skills = [['Python', 8], ['JavaScript', 6], ['Data Science', 7], ['React', 5], ['MySQL', 5], ['Git', 6]];
        $industry = [['Data Engineering', 8], ['Software Development', 6], ['Frontend Development', 6], ['Web Development', 6], ['Backend Development', 5]];
        $traits = [['Analytical', 8], ['Curious', 7], ['Fast Learner', 7], ['Problem Solver', 7]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV8(): array
    {
        $skills = [['C#', 8], ['ASP.NET', 7], ['SQL Server', 6], ['JavaScript', 5], ['TypeScript', 5], ['Git', 6]];
        $industry = [['Backend Development', 7], ['Software Development', 7], ['Web Development', 6], ['QA/Testing', 5], ['System Administration', 5]];
        $traits = [['Detail-oriented', 7], ['Problem Solver', 7], ['Analytical', 6], ['Team Player', 6]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV9(): array
    {
        $skills = [['JavaScript', 6], ['React', 5], ['Git', 6], ['Agile', 7], ['HTML', 5], ['CSS', 5]];
        $industry = [['Software Development', 7], ['Full Stack Development', 6], ['Web Development', 6], ['Frontend Development', 6], ['Backend Development', 5]];
        $traits = [['Problem Solver', 8], ['Team Player', 8], ['Fast Learner', 9], ['Communicative', 7]];
        return array_merge($skills, $industry, $traits);
    }

    private function studentTagPairsV10(): array
    {
        $skills = [['Git', 6], ['Agile', 5], ['HTML', 5], ['CSS', 5], ['JavaScript', 4], ['MySQL', 4]];
        $industry = [['Software Development', 5], ['Web Development', 5], ['IT Support', 5], ['QA/Testing', 5], ['Backend Development', 4]];
        $traits = [['Fast Learner', 6], ['Team Player', 6], ['Curious', 6], ['Adaptable', 6]];
        return array_merge($skills, $industry, $traits);
    }

    private function createStudent(string $first, string $last, string $email): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'role' => UserRole::Student,
                'password_hash' => 'password',
                'first_name' => $first,
                'last_name' => $last,
            ]
        );
        StudentProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'headline' => "{$first} {$last} – Student",
                'bio' => null,
                'searching_status' => 'actively_looking',
            ]
        );
        return $user;
    }

    private function attachStudentTags(User $user, \Illuminate\Support\Collection $tagIds, array $nameWeightPairs): void
    {
        foreach ($nameWeightPairs as [$name, $weight]) {
            $tag = $tagIds->get($name);
            if (! $tag) {
                continue;
            }
            StudentTag::firstOrCreate(
                [
                    'student_user_id' => $user->id,
                    'tag_id' => $tag->id,
                ],
                ['is_active' => true, 'weight' => $weight]
            );
        }
    }

    private function attachStudentMajor(User $user, ?Tag $majorTag): void
    {
        if ($majorTag === null) {
            return;
        }
        StudentTag::firstOrCreate(
            [
                'student_user_id' => $user->id,
                'tag_id' => $majorTag->id,
            ],
            ['is_active' => true, 'weight' => 80]
        );
    }

    private function maybePreference(User $user, ?int $desiredRoleTagId): void
    {
        if ($desiredRoleTagId === null) {
            return;
        }
        StudentPreference::updateOrCreate(
            ['student_user_id' => $user->id],
            [
                'desired_role_tag_id' => $desiredRoleTagId,
                'hours_per_week_min' => 24,
                'hours_per_week_max' => 40,
            ]
        );
    }
}
