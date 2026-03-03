<?php

namespace Modules\Students\Infrastructure\Utils;

use App\Models\Student;

class StudentHelper
{
    /**
     * Get company information for PDF exports
     */
    public static function getCompanyInfo(): array
    {
        $student = Student::first();
        
        if ($student) {
            return [
                'name' => $student->company_name ?: config('app.name', 'V General Contractors'),
                'address' => $student->address ?: '1522 Waugh Dr # 510, Houston, TX 77019',
                'phone' => self::formatPhoneForDisplay($student->phone) ?: '+1 (713) 364-6240',
                'email' => $student->email ?: 'info@vgeneralcontractors.com',
                'website' => $student->website ?: 'https://vgeneralcontractors.com/',
                'logo_path' => public_path('assets/logo/header-document.jpg'),
                'signature_path' => $student->signature_path,
                'latitude' => $student->latitude,
                'longitude' => $student->longitude,
                'facebook_link' => $student->facebook_link,
                'instagram_link' => $student->instagram_link,
                'linkedin_link' => $student->linkedin_link,
                'twitter_link' => $student->twitter_link,
            ];
        }

        // Fallback to defaults if no company data found
        return [
            'name' => config('app.name', 'V General Contractors'),
            'address' => '1522 Waugh Dr # 510, Houston, TX 77019',
            'phone' => '+1 (713) 364-6240',
            'email' => 'info@vgeneralcontractors.com',
            'website' => 'https://vgeneralcontractors.com/',
            'logo_path' => public_path('assets/logo/logo-png.png'),
            'signature_path' => null,
            'latitude' => null,
            'longitude' => null,
            'facebook_link' => null,
            'instagram_link' => null,
            'linkedin_link' => null,
            'twitter_link' => null,
        ];
    }

    /**
     * Format phone number for display
     */
    private static function formatPhoneForDisplay(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format as (xxx) xxx-xxxx for 10 digit numbers
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 4)
            );
        }

        // Format as +1 (xxx) xxx-xxxx for 11 digit numbers starting with 1
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
            return sprintf('+1 (%s) %s-%s', 
                substr($phone, 1, 3),
                substr($phone, 4, 3),
                substr($phone, 7, 4)
            );
        }

        return $phone;
    }

    /**
     * Get company information specifically for email templates
     */
    public static function getEmailTemplateData(): array
    {
        $info = self::getCompanyInfo();
        
        return [
            'company_name' => $info['name'],
            'company_address' => $info['address'],
            'company_phone' => $info['phone'],
            'company_email' => $info['email'],
            'company_website' => $info['website'],
            'company_logo' => $info['logo_path'],
        ];
    }

    /**
     * Get company information for job notifications
     */
    public static function getJobNotificationData(): array
    {
        $info = self::getCompanyInfo();
        
        return [
            'from_name' => $info['name'],
            'from_email' => $info['email'],
            'reply_to' => $info['email'],
            'company_logo' => $info['logo_path'],
            'company_signature' => $info['signature_path'],
        ];
    }

    /**
     * Check if company logo exists
     */
    public static function hasLogo(): bool
    {
        $info = self::getCompanyInfo();
        return !empty($info['logo_path']) && file_exists($info['logo_path']);
    }

    /**
     * Check if company signature exists
     */
    public static function hasSignature(): bool
    {
        $info = self::getCompanyInfo();
        return !empty($info['signature_path']) && file_exists($info['signature_path']);
    }
}
