<?php
class Penduduk{
    private int $id_penduduk = 0;
    private string $nik;
    private string $nama_lengkap;
    private string $tempat_lahir;
    private string $tanggal_lahir;
    private string $jenis_kelamin;
    private string $agama;
    private string $status_perkawinan;
    private string $pekerjaan;
    private string $pendidikan_terakhir;
    private string $kewarganegaraan;
    private string $alamat_domisili;
    private $id_keluarga_fk;
    private string $hubungan_keluarga;

    public function __construct(int $id_penduduk, string $nik, string $nama_lengkap, string $tempat_lahir, string $tanggal_lahir, string $jenis_kelamin, string $agama, string $status_perkawinan, string $pekerjaan, string $pendidikan_terakhir, string $kewarganegaraan, string $alamat_domisili, $id_keluarga_fk) {
        $this->id_penduduk = $id_penduduk;
        $this->nik = $nik;
        $this->nama_lengkap = $nama_lengkap;
        $this->tempat_lahir = $tempat_lahir;
        $this->tanggal_lahir = $tanggal_lahir;
        $this->jenis_kelamin = $jenis_kelamin;
        $this->agama = $agama;
        $this->status_perkawinan = $status_perkawinan;
        $this->pekerjaan = $pekerjaan;
        $this->pendidikan_terakhir = $pendidikan_terakhir;
        $this->kewarganegaraan = $kewarganegaraan;
        $this->alamat_domisili = $alamat_domisili;
        $this->id_keluarga_fk = $id_keluarga_fk;
    }

    public function get_id_penduduk(){
        return $this->id_penduduk;
    }
    public function get_nik(){
        return $this->nik;
    }
    public function get_nama_lengkap(){
        return $this->nama_lengkap;
    }
    public function get_tempat_lahir(){
        return $this->tempat_lahir;
    }
    public function get_tanggal_lahir(){
        return $this->tanggal_lahir;
    }
    public function get_jenis_kelamin(){
        return $this->jenis_kelamin;
    }
    public function get_agama(){
        return $this->agama;
    }
    public function get_pendidikan_terakhir(){
        return $this->pendidikan_terakhir;
    }
    public function get_kewarganegaraan(){
        return $this->kewarganegaraan;
    }
    public function get_alamat_domisili(){
        return $this->alamat_domisili;
    }
    public function get_id_keluarga_fk(){
        return $this->id_keluarga_fk;
    }


    public function set_id_penduduk(int $id_penduduk){
        $this->id_penduduk = $id_penduduk;
    }
    public function set_nik(string $nik){
        $this->nik = $nik;
    }
    public function set_nama_lengkap(string $nama_lengkap){
        $this->nama_lengkap = $nama_lengkap;
    }
    public function set_tempat_lahir(string $tempat_lahir){
        $this->tempat_lahir = $tempat_lahir;
    }
    public function set_tanggal_lahir(string $tanggal_lahir){
        $this->tanggal_lahir = $tanggal_lahir;
    }
    public function set_jenis_kelamin(string $jenis_kelamin){
        $this->jenis_kelamin = $jenis_kelamin;
    }
    public function set_agama(string $agama){
        $this->agama = $agama;
    }
    public function set_status_perkawinan(string $status_perkawinan){
        $this->status_perkawinan = $status_perkawinan;
    }
    public function set_pekerjaan(string $pekerjaan){
        $this->pekerjaan = $pekerjaan;
    }
    public function set_pendidikan_terakhir(string $pendidikan_terakhir){
        $this->pendidikan_terakhir = $pendidikan_terakhir;
    }
    public function set_kewarganegaraan(string $kewarganegaraan){
        $this->kewarganegaraan = $kewarganegaraan;
    }
    public function set_alamat_domisili(string $alamat_domisili){
        $this->alamat_domisili = $alamat_domisili;
    }
    public function set_id_keluarga_fk($id_keluarga_fk){
        $this->id_keluarga_fk = $id_keluarga_fk;
    }
}

class Keluarga {
    private int $id_keluarga;
    private string $nomor_kk;
    private string $rt;
    private string $kepala_keluarga;

    public function __construct(int $id_keluarga, string $nomor_kk, string $rt, string $kepala_keluarga) {
        $this->id_keluarga = $id_keluarga;
        $this->nomor_kk = $nomor_kk;
        $this->rt = $rt;
        $this->kepala_keluarga = $kepala_keluarga;
    }

    public function get_id_keluarga(): int {
        return $this->id_keluarga;
    }

    public function get_nomor_kk(): string {
        return $this->nomor_kk;
    }

    public function get_rt(): string {
        return $this->rt;
    }

    public function get_kepala_keluarga(): string {
        return $this->kepala_keluarga;
    }

    public function set_id_keluarga(int $id_keluarga): void {
        $this->id_keluarga = $id_keluarga;
    }
    public function set_rt(string $rt): void {
        $this->rt = $rt;
    }
    public function set_kepala_keluarga(string $kepala_keluarga): void {
        $this->kepala_keluarga = $kepala_keluarga;
    }

}
