<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Coordinator = 'coordinator';
    case Company = 'company';
}

