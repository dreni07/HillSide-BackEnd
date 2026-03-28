<?php

namespace App\Enums;

enum ProductExtractionSourceType: string
{
    case Pdf = 'pdf';
    case Spreadsheet = 'spreadsheet';
    case Image = 'image';
    case Manual = 'manual';
}
