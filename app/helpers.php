<?php
function imageUploader($file,$path)
{
        $extension = $file->getClientOriginalExtension();

        dd($extension);
        $extension=time().'.'.$extension;
        $file->move(public_path('uploads/'.$path.'/'),$extension);
        $fileName = '/uploads/'.$path.'/'.$extension;
        return $fileName;
}

/**
 * Generate a URL-friendly slug from a string
 * 
 * @param string $string
 * @return string
 */
function slugify($string)
{
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace spaces and special characters with hyphens
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    
    // Remove leading/trailing hyphens
    $string = trim($string, '-');
    
    // Remove multiple consecutive hyphens
    $string = preg_replace('/-+/', '-', $string);
    
    return $string;
}