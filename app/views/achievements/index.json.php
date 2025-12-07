<?php

/** @var \App\Models\Achievement[] $achievements */
/** @var string|null $error */

$json = [
    'success' => true,
    'achievements' => []
];

if (isset($error)) {
    $json['success'] = false;
    $json['error'] = $error;
} else {
    foreach ($achievements as $achievement) {
        $json['achievements'][] = [
            'id' => $achievement->id,
            'title' => $achievement->title,
            'file_path' => $achievement->file_path,
            'uploaded_at' => $achievement->uploaded_at
        ];
    }
}
