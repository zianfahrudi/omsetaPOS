<?php

return [
    /*
    | Ambang anti-fake-GPS untuk presensi mobile.
    */

    // Tolak presensi bila perangkat melaporkan mock/fake location aktif.
    'reject_mock_location' => env('ATTENDANCE_REJECT_MOCK', true),

    // Akurasi GPS terburuk yang masih diterima (meter). Akurasi > nilai ini ditolak.
    // GPS asli biasanya < 50m; nilai sangat besar sering tanda lokasi palsu/jaringan.
    'max_accuracy_meters' => (int) env('ATTENDANCE_MAX_ACCURACY', 100),

    // Toleransi tambahan di luar radius lokasi untuk mengakomodasi drift GPS (meter).
    'radius_buffer_meters' => (int) env('ATTENDANCE_RADIUS_BUFFER', 0),

    // Wajibkan device_id konsisten dengan yang terdaftar pada karyawan (anti pinjam akun).
    'bind_device' => env('ATTENDANCE_BIND_DEVICE', false),
];
