<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithTooManyFields extends Controller
{
    public string $name;
    public string $email;
    public string $phone;
    public string $address;
    public string $city;
    public string $state;
    public string $zipCode;
    public string $country;
    public string $jobTitle;
    public string $department;
    public string $managerName;
    public string $officeLocation;
    public string $startDate;
    public string $endDate;
    public string $emergencyContactName;
    public string $emergencyContactPhone;
    public string $emergencyContactRelation;
}
