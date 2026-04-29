=============================================
DESKRIPSI PROYEK
==================================================
Project ini merupakan aplikasi web berbasis peta yang dirancang untuk memvisualisasikan persebaran kafe di wilayah Bandung. Dataset yang digunakan bersumber dari publikasi resmi Badan Pusat Statistik (BPS) Bandung Barat tahun 2022.

Pada tahap awal, dataset mencakup berbagai kategori tempat usaha seperti rumah makan, restoran, dan kafe. Oleh karena itu, dilakukan proses data preprocessing menggunakan SQL untuk menyaring data sehingga hanya mencakup entitas kafe. Selain itu, dilakukan validasi data dengan mencocokkan setiap entri terhadap Google Maps guna memastikan bahwa kafe yang ditampilkan masih aktif beroperasi.

Hasil dari proses tersebut kemudian diintegrasikan ke dalam sebuah sistem pemetaan berbasis web yang menampilkan distribusi kafe berdasarkan lokasi geografis. Aplikasi ini juga mengelompokkan kafe berdasarkan tema atau konsep bangunan.

==================================================
CARA MENJALANKAN APLIKASI
==================================================
1. Install XAMPP
2. Pindahkan project ke htdocs
3. Jalankan Apache & MySQL
4. Import database kafe.sql ke database "mapping"
5. Akses http://localhost/coffee-mapping

==================================================
CARA MENGGUNAKAN
==================================================
- Klik "Explore the Map"
- Lihat persebaran kafe
- Klik marker untuk detail

==================================================
GITHUB
==================================================
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/username/coffee-mapping.git
git push -u origin main

==================================================
CATATAN
==================================================
- Jangan push config.php
- Gunakan config.example.php
- Jalankan via localhost, bukan Live Server
