<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Seed plantilla-style government position titles.
     *
     * Seed data is based on official Department of Budget and Management
     * Index of Occupational Services / Position Titles references.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Administrative Aide I', 'position_code' => 'ADA1', 'salary_grade' => 1, 'requires_license_number' => false],
            ['name' => 'Administrative Aide II', 'position_code' => 'ADA2', 'salary_grade' => 2, 'requires_license_number' => false],
            ['name' => 'Administrative Aide III', 'position_code' => 'ADA3', 'salary_grade' => 3, 'requires_license_number' => false],
            ['name' => 'Administrative Aide IV', 'position_code' => 'ADA4', 'salary_grade' => 4, 'requires_license_number' => false],
            ['name' => 'Administrative Aide V', 'position_code' => 'ADA5', 'salary_grade' => 5, 'requires_license_number' => false],
            ['name' => 'Administrative Aide VI', 'position_code' => 'ADA6', 'salary_grade' => 6, 'requires_license_number' => false],
            ['name' => 'Administrative Assistant I', 'position_code' => 'ADAS1', 'salary_grade' => 7, 'requires_license_number' => false],
            ['name' => 'Administrative Assistant II', 'position_code' => 'ADAS2', 'salary_grade' => 8, 'requires_license_number' => false],
            ['name' => 'Administrative Assistant III', 'position_code' => 'ADAS3', 'salary_grade' => 9, 'requires_license_number' => false],
            ['name' => 'Administrative Assistant IV', 'position_code' => 'ADAS4', 'salary_grade' => 10, 'requires_license_number' => false],
            ['name' => 'Administrative Assistant V', 'position_code' => 'ADAS5', 'salary_grade' => 11, 'requires_license_number' => false],
            ['name' => 'Administrative Assistant VI', 'position_code' => 'ADAS6', 'salary_grade' => 12, 'requires_license_number' => false],
            ['name' => 'Senior Administrative Assistant I', 'position_code' => 'SADAS1', 'salary_grade' => 13, 'requires_license_number' => false],
            ['name' => 'Administrative Officer I', 'position_code' => 'ADO1', 'salary_grade' => 11, 'requires_license_number' => false],
            ['name' => 'Administrative Officer II', 'position_code' => 'ADO2', 'salary_grade' => 15, 'requires_license_number' => false],
            ['name' => 'Administrative Officer III', 'position_code' => 'ADO3', 'salary_grade' => 18, 'requires_license_number' => false],
            ['name' => 'Administrative Officer IV', 'position_code' => 'ADO4', 'salary_grade' => 22, 'requires_license_number' => false],
            ['name' => 'Administrative Officer V', 'position_code' => 'ADO5', 'salary_grade' => 24, 'requires_license_number' => false],
            ['name' => 'Chief Administrative Officer', 'position_code' => 'CADOF', 'salary_grade' => 24, 'requires_license_number' => false],
            ['name' => 'Planning Assistant', 'position_code' => null, 'salary_grade' => 8, 'requires_license_number' => false],
            ['name' => 'Planning Officer I', 'position_code' => null, 'salary_grade' => 11, 'requires_license_number' => false],
            ['name' => 'Planning Officer II', 'position_code' => null, 'salary_grade' => 15, 'requires_license_number' => false],
            ['name' => 'Planning Officer III', 'position_code' => null, 'salary_grade' => 18, 'requires_license_number' => false],
            ['name' => 'Planning Officer IV', 'position_code' => null, 'salary_grade' => 22, 'requires_license_number' => false],
            ['name' => 'Planning Officer V', 'position_code' => null, 'salary_grade' => 24, 'requires_license_number' => false],
            ['name' => 'Project Development Assistant', 'position_code' => null, 'salary_grade' => 8, 'requires_license_number' => false],
            ['name' => 'Project Development Officer I', 'position_code' => 'PDO1', 'salary_grade' => 11, 'requires_license_number' => false],
            ['name' => 'Project Development Officer II', 'position_code' => 'PDO2', 'salary_grade' => 15, 'requires_license_number' => false],
            ['name' => 'Project Development Officer III', 'position_code' => 'PDO3', 'salary_grade' => 18, 'requires_license_number' => false],
            ['name' => 'Project Development Officer IV', 'position_code' => 'PDO4', 'salary_grade' => 22, 'requires_license_number' => false],
            ['name' => 'Project Development Officer V', 'position_code' => 'PDO5', 'salary_grade' => 24, 'requires_license_number' => false],
            ['name' => 'Project Evaluation Assistant', 'position_code' => null, 'salary_grade' => 8, 'requires_license_number' => false],
            ['name' => 'Project Evaluation Officer I', 'position_code' => null, 'salary_grade' => 11, 'requires_license_number' => false],
            ['name' => 'Project Evaluation Officer II', 'position_code' => null, 'salary_grade' => 15, 'requires_license_number' => false],
            ['name' => 'Project Evaluation Officer III', 'position_code' => null, 'salary_grade' => 18, 'requires_license_number' => false],
            ['name' => 'Project Evaluation Officer IV', 'position_code' => null, 'salary_grade' => 22, 'requires_license_number' => false],
            ['name' => 'Project Evaluation Officer V', 'position_code' => null, 'salary_grade' => 24, 'requires_license_number' => false],
            ['name' => 'Information Officer I', 'position_code' => 'INFO1', 'salary_grade' => 11, 'requires_license_number' => false],
            ['name' => 'Social Welfare Aide', 'position_code' => 'SOCWA', 'salary_grade' => 4, 'requires_license_number' => false],
            ['name' => 'Social Welfare Assistant', 'position_code' => 'SOCWAS', 'salary_grade' => 8, 'requires_license_number' => false],
            ['name' => 'Social Welfare Officer I', 'position_code' => 'SOCWO1', 'salary_grade' => 11, 'requires_license_number' => true],
            ['name' => 'Social Welfare Officer II', 'position_code' => 'SOCWO2', 'salary_grade' => 15, 'requires_license_number' => true],
            ['name' => 'Social Welfare Officer III', 'position_code' => 'SOCWO3', 'salary_grade' => 18, 'requires_license_number' => true],
            ['name' => 'Social Welfare Officer IV', 'position_code' => 'SOCWO4', 'salary_grade' => 22, 'requires_license_number' => true],
            ['name' => 'Social Welfare Officer V', 'position_code' => 'SOCWO5', 'salary_grade' => 24, 'requires_license_number' => true],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['name' => $position['name']],
                $position + ['is_active' => true]
            );
        }
    }
}
