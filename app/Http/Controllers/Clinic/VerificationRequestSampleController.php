<?php

namespace App\Http\Controllers\Clinic;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;

class VerificationRequestSampleController
{
    public function __invoke(): Response
    {
        $path = collect([
            base_path('verification-request-import-sample.xlsx'),
            storage_path('app/templates/verification-request-import-sample.xlsx'),
            public_path('samples/verification-request-import-sample.xlsx'),
        ])->first(fn (string $candidate): bool => is_file($candidate));

        if (! $path) {
            throw new FileNotFoundException('Verification request sample workbook was not found.');
        }

        return response()->download(
            $path,
            'verification-request-import-sample.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }
}
