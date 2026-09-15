<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
@vite('resources/css/app.css')
<link class="icon" href="{{ asset('images/artur.png') }}" type="image/png" />
<title>Absen Masuk</title>

{{-- Material Symbols - Ditambah &display=block untuk mencegah muncul teks saat sinyal lambat --}}
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet" />

<script defer src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

<style>
.spinner {
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top: 3px solid #ffffff;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
    display: inline-block;
    margin-right: 5px;
    vertical-align: middle;
}
.spinner-green {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #065f46;
}
@keyframes spin { 
    0% { transform: rotate(0deg); } 
    100% { transform: rotate(360deg); } 
}
button:disabled {
    cursor: not-allowed;
    background-color: #9ca3af !important;
}

#captureBtn:disabled {
    background-color: #9ca3af !important;
    cursor: not-allowed !important;
    opacity: 0.5 !important;
    pointer-events: none !important;
    transform: none !important;
}
#captureBtn:not(:disabled) {
    background-color: #047857 !important;
    cursor: pointer !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}
#captureBtn:not(:disabled):hover {
    background-color: #065f46 !important;
    transform: scale(1.02);
}

#video-container {
    position: relative;
    background: #000;
    border-radius: 8px;
    overflow: hidden;
    width: 100%;
    aspect-ratio: 4/3;
    min-height: 280px;
}
#video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1);
}
#face-indicator, #lighting-indicator {
    position: absolute;
    top: 8px;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    color: white;
    font-size: 9px;
    padding: 3px 8px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 3px;
    z-index: 10;
}
#face-indicator { left: 8px; }
#lighting-indicator { right: 8px; }
#face-indicator .material-symbols-outlined,
#lighting-indicator .material-symbols-outlined {
    font-size: 12px;
}
.badge-hd {
    position: absolute;
    bottom: 8px;
    left: 8px;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    color: white;
    font-size: 9px;
    padding: 2px 8px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 3px;
    z-index: 10;
}
.badge-hd .material-symbols-outlined { font-size: 12px; }
#photo-result {
    text-align: center;
}
#photo-result img {
    max-height: 300px;
    max-width: 100%;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.btn-retake {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: 0.2s;
    z-index: 20;
}
.btn-retake:hover {
    transform: scale(1.1) rotate(180deg);
    background: #dc2626;
}
.photo-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    margin-top: 4px;
    font-size: 12px;
    color: #16a34a;
}
.photo-status .material-symbols-outlined { font-size: 14px; }

.status-success { color: #4ade80; }
.status-error { color: #ef4444; }
.status-warning { color: #fbbf24; }

/* Trik CSS tambahan untuk mencegah teks jelek muncul saat font belum load */
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    direction: ltr;
    display: inline-block;
    white-space: nowrap;
    word-wrap: normal;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
    -moz-osx-font-smoothing: grayscale;
}

.label-foto {
    font-size: 13px !important;
    font-weight: 600 !important;
}
.label-foto .material-symbols-outlined {
    font-size: 18px !important;
}
.label-foto .text-red-500 {
    font-size: 10px !important;
}

/* Status visual */
#status-indicator {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 8px;
    font-size: 12px;
}
.status-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.status-item .material-symbols-outlined {
    font-size: 16px;
}
.status-item.ready .material-symbols-outlined { color: #16a34a; }
.status-item.not-ready .material-symbols-outlined { color: #ef4444; }

/* Pesan error di bawah tombol */
#submitStatusMessage {
    font-size: 13px;
    color: #dc2626;
    text-align: center;
    min-height: 20px;
}
</style>
</head>
<body class="bg-gray-200">

@php
use Filament\Facades\Filament;
$user = Filament::auth()->user();
$defaultAvatar = $user 
    ? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random' 
    : 'https://ui-avatars.com/api/?name=User&background=random';
$avatarUrl = $user 
    ? ($user->getFilamentAvatarUrl() ?: $defaultAvatar) 
    : $defaultAvatar;
@endphp

<div class="max-w-[420px] mx-auto bg-white border-2 border-gray-200 rounded-md shadow-lg relative pb-6">

<a href="{{ url('/') }}" 
   style="top: 24px; left: 24px;"
   class="absolute bg-white/20 hover:bg-white/30 border border-white/40 text-white py-1.5 px-3 rounded-lg text-sm font-semibold shadow-sm backdrop-blur-sm z-10 transition flex items-center gap-1">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
    </svg>
    Keluar
</a>

<div class="px-6 h-[220px] bg-gradient-to-r from-emerald-900 to-emerald-700 text-white rounded-b-3xl flex items-center justify-between border-b-8 border-amber-500">
    <div>
        <h2 class="py-1 font-bold text-lg">{{ $user?->name ?? 'User' }}</h2>
        <h2 class="text-[16px]">{{ $user?->employe?->position ?? 'Karyawan' }}</h2>
        <!-- {{-- TAMBAHAN CABANG --}}
        <h2 class="text-[14px] opacity-80 mt-1">
            {{ $user->employe->branch->name ?? 'Cabang tidak ditentukan' }}
        </h2> -->
    </div>
    
    <div class="w-16 h-16 rounded-full border-2 border-gray-100 overflow-hidden relative bg-amber-500 flex items-center justify-center text-white font-bold text-xl shadow-sm select-none">
        <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
        <img src="{{ $avatarUrl }}" 
             onerror="this.style.display='none';"
             class="absolute inset-0 w-full h-full object-cover" 
             alt="{{ $user->name }}" />
    </div>
</div>

<div class="relative -mt-7 max-w-[380px] mx-auto p-4 bg-gradient-to-br from-white to-gray-100 rounded-2xl shadow-lg border border-gray-200 text-center">
    <img src="{{ asset('images/iconabsen.png') }}" alt="Icon Absen" class="w-10 h-10 mx-auto" />
    <h1 class="text-xl font-semibold">Absen Masuk</h1>
    <h2 class="text-lg">{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</h2>
</div>

@if (session('success'))
<div class="bg-green-100 text-green-800 p-4 rounded-md max-w-[380px] mx-auto mt-4 text-center border border-green-300">
    {{ session('success') }}
</div>
@endif

@if (session('error'))
<div class="bg-red-100 text-red-800 p-4 rounded-md max-w-[380px] mx-auto mt-4 text-center border border-red-300">
    {{ session('error') }}
</div>
@endif

@if ($errors->any())
<div class="bg-red-100 text-red-700 p-4 rounded-md m-4">
    <strong>Ada kesalahan:</strong>
    <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form id="absenForm" action="{{ route('absenmasuk.absenMasuk') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="max-w-[380px] mx-auto my-6 p-4 bg-gradient-to-br from-white to-gray-100 rounded-2xl shadow border border-gray-200 text-center">
    <img src="{{ asset('images/iconloc.png') }}" alt="Icon Lokasi" class="w-12 h-12 mx-auto mt-2" />
    <h1 class="text-xl font-semibold py-2">Ambil Lokasi Anda</h1>
    <button type="button" id="getLocation" class="mt-2 bg-emerald-800 text-white py-2 px-4 rounded-lg hover:bg-emerald-700 transition">
        Dapatkan Lokasi
    </button>
    <div id="notification" class="hidden mt-2 p-2 rounded text-white text-sm"></div>
</div>

<input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
<input type="hidden" name="latitude" id="latitude" value="">
<input type="hidden" name="longitude" id="longitude" value="">
<input type="hidden" name="foto" id="fotoBase64" value="">
<input type="hidden" name="time_attendance" id="time_attendance" value="{{ \Carbon\Carbon::now()->locale('id') }}">
<input type="hidden" id="photo_taken" value="0">
<input type="hidden" id="face_confirmed" value="0">

<div class="max-w-[380px] mx-auto my-6 p-4 bg-gradient-to-br from-white to-gray-100 rounded-2xl shadow border border-gray-200 text-center">
    <label class="block mb-2 text-gray-900 flex items-center justify-center gap-2 label-foto">
        <span class="material-symbols-outlined text-emerald-600">camera_alt</span>
        Foto Selfie <span class="text-red-500">(Wajib dari Kamera)</span>
    </label>

    <div id="camera-area">
        <div id="video-container" class="hidden">
            <video id="video" autoplay playsinline muted></video>
            <div id="face-indicator" class="hidden">
                <span class="material-symbols-outlined">face</span>
                <span id="faceStatus">Mendeteksi...</span>
            </div>
            <div id="lighting-indicator" class="hidden">
                <span class="material-symbols-outlined">light</span>
                <span id="lightingStatus">Cahaya...</span>
            </div>
            <div class="badge-hd">
                <span class="material-symbols-outlined">videocam</span> HD
            </div>
        </div>

        <div id="camera-start" class="text-center">
            <button type="button" id="startCameraBtn" class="w-full px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white rounded-lg transition flex items-center justify-center gap-2 text-sm">
                <span class="material-symbols-outlined">videocam</span>
                Buka Kamera
            </button>
            <p style="font-size:10px; color:#94a3b8; margin-top:8px; text-align:center;">
                ⚠️ Foto HARUS diambil langsung dari kamera
            </p>
        </div>

        <div id="capture-area" class="hidden mt-3">
            <button type="button" id="captureBtn" class="w-full px-4 py-2 bg-gray-400 text-white rounded-lg transition flex items-center justify-center gap-2 text-sm" disabled>
                <span class="material-symbols-outlined">camera</span>
                Ambil Foto
            </button>
            <p id="captureHint" class="text-xs text-slate-400 mt-1 text-center">⏳ Menunggu deteksi wajah...</p>
        </div>
    </div>

    <div id="photo-result" class="hidden mt-3 text-center">
        <div class="relative inline-block">
            <img id="photo" src="" alt="Foto Selfie" />
            <button type="button" id="retakeBtn" class="btn-retake">
                <span class="material-symbols-outlined">refresh</span>
            </button>
        </div>
        <div class="photo-status">
            <span class="material-symbols-outlined">check_circle</span>
            <span>Foto berhasil diambil</span>
        </div>
    </div>

    <div id="photoWarning" class="hidden mt-2 text-red-600 text-sm text-center"></div>

    <!-- Status visual -->
    <div id="status-indicator" class="hidden">
        <div class="status-item not-ready" id="statusFace">
            <span class="material-symbols-outlined">face</span>
            <span>Wajah: <span id="statusFaceText">Belum</span></span>
        </div>
        <div class="status-item not-ready" id="statusPhotoItem">
            <span class="material-symbols-outlined">photo_camera</span>
            <span>Foto: <span id="statusPhotoText">Belum</span></span>
        </div>
    </div>
</div>

<div class="p-4">
    <label for="desc" class="block text-xl font-semibold text-center mb-2">Keterangan</label>
    <textarea name="desc" id="desc" rows="4" class="w-full p-2.5 text-sm bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Lokasi Event (ICE BSD, RCPP, KOKAS....)"></textarea>
</div>

<div class="flex flex-col items-center justify-center py-4 gap-2">
    <div id="submitStatusMessage"></div>
    <button type="submit" id="submitBtn" 
        class="bg-gray-400 text-white py-3 px-6 rounded-lg cursor-not-allowed transition duration-200 text-[15px] font-semibold flex items-center justify-center gap-2 w-56" disabled>
        Absen Masuk
    </button>
</div>
</form>
</div>

<script>
// ==============================
//  DOM Elements
// ==============================
const notif = document.getElementById('notification');
const latitude = document.getElementById('latitude');
const longitude = document.getElementById('longitude');
const fotoBase64 = document.getElementById('fotoBase64');
const photoWarning = document.getElementById('photoWarning');
const submitBtn = document.getElementById('submitBtn');
const video = document.getElementById('video');
const canvas = document.createElement('canvas');
const photo = document.getElementById('photo');
const photoResult = document.getElementById('photo-result');
const photoTaken = document.getElementById('photo_taken');
const faceConfirmed = document.getElementById('face_confirmed');
const desc = document.getElementById('desc');
const submitStatusMessage = document.getElementById('submitStatusMessage');

// Status visual
const statusIndicator = document.getElementById('status-indicator');
const statusFace = document.getElementById('statusFace');
const statusFaceText = document.getElementById('statusFaceText');
const statusPhotoItem = document.getElementById('statusPhotoItem');
const statusPhotoText = document.getElementById('statusPhotoText');

const startCameraBtn = document.getElementById('startCameraBtn');
const captureBtn = document.getElementById('captureBtn');
const retakeBtn = document.getElementById('retakeBtn');
const captureHint = document.getElementById('captureHint');
const faceIndicator = document.getElementById('face-indicator');
const faceStatus = document.getElementById('faceStatus');
const lightingIndicator = document.getElementById('lighting-indicator');
const lightingStatus = document.getElementById('lightingStatus');
const videoContainer = document.getElementById('video-container');
const cameraStart = document.getElementById('camera-start');
const captureArea = document.getElementById('capture-area');

let stream = null;
let isCameraActive = false;
let faceDetectionInterval = null;
let isCapturing = false;
let faceapiLoaded = false;
let faceDetectedCount = 0;
const MIN_FACE_DETECTED = 1;
let faceapiLoading = false;
let faceapiFallback = false;

// ==============================
//  Load FaceAPI dengan fallback
// ==============================
async function loadFaceAPI() {
    if (faceapiLoaded || faceapiLoading) return;
    try {
        faceapiLoading = true;
        await faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/');
        faceapiLoaded = true;
        faceapiLoading = false;
        console.log('FaceAPI loaded');
    } catch (err) {
        console.error('FaceAPI load error:', err);
        faceapiLoading = false;
        faceapiLoaded = false;
        faceapiFallback = true;
        photoWarning.innerText = '⚠️ Deteksi wajah tidak tersedia, melanjutkan tanpa deteksi.';
        photoWarning.classList.remove('hidden');
        faceConfirmed.value = '1';
        updateStatusVisual();
        captureBtn.disabled = false;
        captureBtn.classList.remove('bg-gray-400');
        captureBtn.classList.add('bg-emerald-700', 'hover:bg-emerald-600');
        captureHint.textContent = '✅ Mode fallback: wajah dianggap terdeteksi';
        captureHint.className = 'text-xs text-yellow-600 mt-1 text-center font-medium';
    }
}

// ==============================
//  Deteksi Wajah
// ==============================
async function detectFace(videoElement) {
    if (faceapiFallback) {
        return { detected: true, hasFace: true, isBright: true, faceCount: 1 };
    }
    if (!faceapiLoaded) {
        return { detected: true, hasFace: true, isBright: true, faceCount: 1 };
    }
    try {
        const detections = await faceapi.detectAllFaces(
            videoElement,
            new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.25 })
        );
        const hasFace = detections.length > 0;
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        tempCanvas.width = 80;
        tempCanvas.height = 60;
        tempCtx.drawImage(videoElement, 0, 0, 80, 60);
        const imageData = tempCtx.getImageData(0, 0, 80, 60);
        const data = imageData.data;
        let brightness = 0;
        for (let i = 0; i < data.length; i += 4) {
            brightness += (data[i] + data[i+1] + data[i+2]) / 3;
        }
        brightness = brightness / (data.length / 4);
        const isBright = brightness > 20;
        return { detected: hasFace && isBright, hasFace, isBright, faceCount: detections.length };
    } catch (err) {
        console.error('Face detection error:', err);
        return { detected: true, hasFace: true, isBright: true, faceCount: 1 };
    }
}

function startFaceDetection() {
    if (faceDetectionInterval) clearInterval(faceDetectionInterval);
    faceDetectionInterval = setInterval(async () => {
        if (!isCameraActive || !video.videoWidth || !video.videoHeight) return;
        const result = await detectFace(video);
        if (result.detected && result.hasFace) {
            faceStatus.textContent = '✓ Wajah terdeteksi';
            faceStatus.className = 'status-success';
            lightingStatus.textContent = result.isBright ? '✓ Cahaya cukup' : '⚠️ Cahaya kurang';
            lightingStatus.className = result.isBright ? 'status-success' : 'status-warning';
            faceDetectedCount++;
            if (faceDetectedCount >= MIN_FACE_DETECTED) {
                faceConfirmed.value = '1';
                captureBtn.disabled = false;
                captureBtn.classList.remove('bg-gray-400');
                captureBtn.classList.add('bg-emerald-700', 'hover:bg-emerald-600');
                captureHint.textContent = '✅ Wajah terdeteksi, silakan ambil foto';
                captureHint.className = 'text-xs text-green-600 mt-1 text-center font-medium';
                updateStatusVisual();
            }
        } else {
            faceDetectedCount = 0;
            if (photoTaken.value !== '1') {
                faceConfirmed.value = '0';
                captureBtn.disabled = true;
                captureBtn.classList.add('bg-gray-400');
                captureBtn.classList.remove('bg-emerald-700', 'hover:bg-emerald-600');
            }
            if (!result.hasFace) {
                faceStatus.textContent = '❌ Tidak ada wajah';
                faceStatus.className = 'status-error';
                lightingStatus.textContent = result.isBright ? '✓ Cahaya cukup' : '⚠️ Cahaya kurang';
                lightingStatus.className = result.isBright ? 'status-success' : 'status-warning';
                if (photoTaken.value !== '1') {
                    captureHint.textContent = '❌ Pastikan wajah terlihat di kamera';
                    captureHint.className = 'text-xs text-red-600 mt-1 text-center font-medium';
                }
            } else if (!result.isBright) {
                faceStatus.textContent = '✓ Wajah terdeteksi';
                faceStatus.className = 'status-success';
                lightingStatus.textContent = '⚠️ Cahaya terlalu redup';
                lightingStatus.className = 'status-warning';
                if (photoTaken.value !== '1') {
                    captureHint.textContent = '⚠️ Tambah pencahayaan!';
                    captureHint.className = 'text-xs text-yellow-600 mt-1 text-center font-medium';
                }
            } else {
                faceStatus.textContent = '❌ Wajah tidak jelas';
                faceStatus.className = 'status-error';
                lightingStatus.textContent = '✓ Cahaya cukup';
                lightingStatus.className = 'status-success';
                if (photoTaken.value !== '1') {
                    captureHint.textContent = '❌ Pastikan wajah terlihat jelas';
                    captureHint.className = 'text-xs text-red-600 mt-1 text-center font-medium';
                }
            }
            updateStatusVisual();
        }
        if (photoTaken.value !== '1') {
            enableSubmitCheck();
        }
    }, 200);
}

// ==============================
//  Update Status Visual
// ==============================
function updateStatusVisual() {
    statusIndicator.classList.remove('hidden');
    const faceOk = faceConfirmed.value === '1';
    const photoOk = photoTaken.value === '1';

    statusFace.className = 'status-item ' + (faceOk ? 'ready' : 'not-ready');
    statusFaceText.textContent = faceOk ? 'Siap' : 'Belum';

    statusPhotoItem.className = 'status-item ' + (photoOk ? 'ready' : 'not-ready');
    statusPhotoText.textContent = photoOk ? 'Sudah' : 'Belum';
}

// ==============================
//  Enable / Disable Submit - gabungkan foto & wajah
// ==============================
function enableSubmitCheck() {
    const lat = latitude.value;
    const lng = longitude.value;
    const foto = fotoBase64.value;
    const photoOk = photoTaken.value === '1';
    const faceOk = faceConfirmed.value === '1';
    const descOk = desc.value.trim().length > 0;

    const lokasiOk = lat && lng;
    const fotoOk = foto && photoOk && faceOk;
    const allOk = lokasiOk && fotoOk && descOk;

    updateStatusVisual();

    if (allOk) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
        submitBtn.classList.add('bg-emerald-800', 'hover:bg-emerald-700');
        submitBtn.innerHTML = 'Absen Masuk'; // Kembalikan text original
        submitStatusMessage.textContent = '';
    } else {
        submitBtn.disabled = true;
        submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
        submitBtn.classList.remove('bg-emerald-800', 'hover:bg-emerald-700');
        submitBtn.innerHTML = 'Absen Masuk';
        let missing = [];
        if (!lokasiOk) missing.push('Lokasi');
        if (!fotoOk) missing.push('Foto');
        if (!descOk) missing.push('Keterangan');
        submitStatusMessage.textContent = '⚠️ Syarat kurang: ' + missing.join(', ');
    }
}

// ==============================
//  Kamera
// ==============================
startCameraBtn.addEventListener('click', async function() {
    if (isCameraActive) return;
    if (!faceapiLoaded && !faceapiLoading && !faceapiFallback) {
        loadFaceAPI();
    }

    try {
        startCameraBtn.disabled = true;
        startCameraBtn.innerHTML = '<span class="spinner spinner-green"></span> Membuka...';

        // ✅ PERBAIKAN 1: Sederhanakan constraint (hilangkan width, height, frameRate)
        const constraints = {
            video: { 
                facingMode: 'user' 
            },
            audio: false
        };

        stream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = stream;

        // ✅ PERBAIKAN 2: Tambahkan Timeout 10 detik agar tidak muter selamanya
        await Promise.race([
            new Promise((resolve) => {
                if (video.readyState >= 2) resolve();
                else video.onloadedmetadata = resolve;
            }),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('TIMEOUT')), 10000)
            )
        ]);

        await video.play();
        isCameraActive = true;

        // Lanjutkan ke UI (show video, hide tombol mulai, dll)
        videoContainer.classList.remove('hidden');
        cameraStart.classList.add('hidden');
        captureArea.classList.remove('hidden');
        faceIndicator.classList.remove('hidden');
        lightingIndicator.classList.remove('hidden');
        statusIndicator.classList.remove('hidden');

        faceDetectedCount = 0;
        if (!faceapiFallback) {
            faceConfirmed.value = '0';
            captureBtn.disabled = true;
            captureBtn.classList.add('bg-gray-400');
            captureBtn.classList.remove('bg-emerald-700', 'hover:bg-emerald-600');
            captureHint.textContent = '⏳ Menunggu deteksi wajah...';
            captureHint.className = 'text-xs text-slate-400 mt-1 text-center';
        } else {
            captureBtn.disabled = false;
            captureBtn.classList.remove('bg-gray-400');
            captureBtn.classList.add('bg-emerald-700', 'hover:bg-emerald-600');
            captureHint.textContent = '✅ Mode fallback: wajah dianggap terdeteksi';
            captureHint.className = 'text-xs text-yellow-600 mt-1 text-center font-medium';
        }

        startFaceDetection();
        startCameraBtn.disabled = false;
        startCameraBtn.innerHTML = '<span class="material-symbols-outlined">videocam</span> Buka Kamera';
        photoWarning.classList.add('hidden');

    } catch (err) {
        console.error('Camera error:', err);
        startCameraBtn.disabled = false;
        startCameraBtn.innerHTML = '<span class="material-symbols-outlined">videocam</span> Buka Kamera';
        
        let errorMsg = '⚠️ Gagal mengakses kamera: ';
        if (err.message === 'TIMEOUT') {
            errorMsg += 'iPhone terlalu lama merespon kamera. Coba tutup semua aplikasi kamera lain atau restart HP.';
        } else if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            errorMsg += 'Izin kamera ditolak. Cek Pengaturan > Safari > Kamera > Izinkan.';
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            errorMsg += 'Tidak ada kamera depan terdeteksi.';
        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
            errorMsg += 'Kamera sedang digunakan oleh aplikasi lain.';
        } else {
            errorMsg += err.message || 'Coba refresh halaman.';
        }
        photoWarning.innerText = errorMsg;
        photoWarning.classList.remove('hidden');
    }
});

// ==============================
//  FUNGSI AUTO WHITE BALANCE
// ==============================
function autoWhiteBalance(ctx, width, height) {
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    let sumR = 0, sumG = 0, sumB = 0;
    const len = data.length;
    for (let i = 0; i < len; i += 4) {
        sumR += data[i];
        sumG += data[i+1];
        sumB += data[i+2];
    }
    const pixelCount = len / 4;
    const avgR = sumR / pixelCount;
    const avgG = sumG / pixelCount;
    const avgB = sumB / pixelCount;
    const avgGray = (avgR + avgG + avgB) / 3;
    if (avgGray < 1) return;
    const scaleR = avgGray / avgR;
    const scaleG = avgGray / avgG;
    const scaleB = avgGray / avgB;
    for (let i = 0; i < len; i += 4) {
        data[i] = Math.min(255, Math.max(0, data[i] * scaleR));
        data[i+1] = Math.min(255, Math.max(0, data[i+1] * scaleG));
        data[i+2] = Math.min(255, Math.max(0, data[i+2] * scaleB));
    }
    ctx.putImageData(imageData, 0, 0);
}

// ==============================
//  Fungsi auto-enhance
// ==============================
function applyAutoEnhance(ctx, width, height) {
    autoWhiteBalance(ctx, width, height);

    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    const len = data.length;
    const brightness = 5;
    const contrast = 1.1;
    const saturation = 1.15;

    for (let i = 0; i < len; i += 4) {
        let r = data[i];
        let g = data[i+1];
        let b = data[i+2];

        r += brightness;
        g += brightness;
        b += brightness;

        r = (r - 128) * contrast + 128;
        g = (g - 128) * contrast + 128;
        b = (b - 128) * contrast + 128;

        const gray = 0.2989 * r + 0.5870 * g + 0.1140 * b;
        r = gray + saturation * (r - gray);
        g = gray + saturation * (g - gray);
        b = gray + saturation * (b - gray);

        data[i] = Math.min(255, Math.max(0, r));
        data[i+1] = Math.min(255, Math.max(0, g));
        data[i+2] = Math.min(255, Math.max(0, b));
    }
    ctx.putImageData(imageData, 0, 0);
}

// ==============================
//  Capture Photo
// ==============================
captureBtn.addEventListener('click', function() {
    if (captureBtn.disabled) return;
    
    if (!isCameraActive || !video.videoWidth || !video.videoHeight) {
        photoWarning.innerText = '⚠️ Kamera belum siap.';
        photoWarning.classList.remove('hidden');
        return;
    }
    if (!faceapiFallback && faceConfirmed.value !== '1') {
        photoWarning.innerText = '❌ Wajah belum terdeteksi!';
        photoWarning.classList.remove('hidden');
        return;
    }

    isCapturing = true;
    captureBtn.disabled = true;
    captureBtn.innerHTML = '<span class="spinner"></span> Memproses...';

    setTimeout(() => {
        try {
            const videoWidth = video.videoWidth;
            const videoHeight = video.videoHeight;
            const cropSize = Math.min(videoWidth, videoHeight);
            const sx = (videoWidth - cropSize) / 2;
            const sy = (videoHeight - cropSize) / 2;

            const size = 400;
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');

            ctx.save();
            ctx.translate(size, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, sx, sy, cropSize, cropSize, 0, 0, size, size);
            ctx.restore();

            applyAutoEnhance(ctx, size, size);

            const dataURL = canvas.toDataURL('image/jpeg', 0.85);
            fotoBase64.value = dataURL;
            photo.src = dataURL;
            photoTaken.value = '1';

            if (faceDetectionInterval) {
                clearInterval(faceDetectionInterval);
                faceDetectionInterval = null;
            }

            photoResult.classList.remove('hidden');
            videoContainer.classList.add('hidden');
            captureArea.classList.add('hidden');
            cameraStart.classList.add('hidden');
            faceIndicator.classList.add('hidden');
            lightingIndicator.classList.add('hidden');

            photoWarning.classList.add('hidden');

            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                isCameraActive = false;
            }

            updateStatusVisual();
            enableSubmitCheck();

        } catch (err) {
            console.error('Capture error:', err);
            photoWarning.innerText = '⚠️ Gagal mengambil foto: ' + err.message;
            photoWarning.classList.remove('hidden');
        } finally {
            isCapturing = false;
            captureBtn.innerHTML = '<span class="material-symbols-outlined">camera</span> Ambil Foto';
        }
    }, 200);
});

// ==============================
//  Retake
// ==============================
retakeBtn.addEventListener('click', function() {
    photoResult.classList.add('hidden');
    cameraStart.classList.remove('hidden');
    fotoBase64.value = '';
    photoTaken.value = '0';
    photo.src = '';
    if (!faceapiFallback) {
        faceConfirmed.value = '0';
    } else {
        faceConfirmed.value = '1';
    }
    faceDetectedCount = 0;
    photoWarning.classList.add('hidden');
    faceIndicator.classList.add('hidden');
    lightingIndicator.classList.add('hidden');
    videoContainer.classList.add('hidden');
    captureArea.classList.add('hidden');
    statusIndicator.classList.add('hidden');
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
        isCameraActive = false;
    }
    captureBtn.disabled = true;
    captureBtn.classList.add('bg-gray-400');
    captureBtn.classList.remove('bg-emerald-700', 'hover:bg-emerald-600');
    captureHint.textContent = '⏳ Menunggu deteksi wajah...';
    captureHint.className = 'text-xs text-slate-400 mt-1 text-center';

    updateStatusVisual();
    enableSubmitCheck();
});

// ==============================
//  Lokasi
// ==============================
document.getElementById('getLocation').addEventListener('click', function () {
    notif.innerHTML = '<span class="spinner spinner-green"></span> Mencari lokasi...';
    notif.classList.remove('hidden', 'bg-red-500', 'bg-green-500');
    notif.classList.add('bg-amber-500');

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                latitude.value = pos.coords.latitude;
                longitude.value = pos.coords.longitude;
                notif.innerText = '✅ Lokasi berhasil diambil';
                notif.classList.remove('bg-amber-500');
                notif.classList.add('bg-green-500');
                enableSubmitCheck();
            },
            err => {
                let msg = '';
                switch(err.code){
                    case err.PERMISSION_DENIED: msg = '❌ Akses lokasi ditolak.'; break;
                    case err.POSITION_UNAVAILABLE: msg = '❌ Lokasi tidak tersedia.'; break;
                    case err.TIMEOUT: msg = '⏰ Lokasi tidak ditemukan. Hidupkan lokasi'; break;
                    default: msg = '⚠️ Gagal mengambil lokasi.';
                }
                notif.innerText = msg;
                notif.classList.remove('bg-amber-500');
                notif.classList.add('bg-red-500');
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
        );
    } else {
        notif.innerText = '❌ Browser tidak mendukung geolokasi.';
        notif.classList.remove('bg-amber-500');
        notif.classList.add('bg-red-500');
    }
});

// ==============================
//  Event listener untuk keterangan
// ==============================
desc.addEventListener('input', function() {
    enableSubmitCheck();
});
desc.addEventListener('change', function() {
    enableSubmitCheck();
});

// ==============================
//  Validasi submit (Perbaikan Double Click / Loading)
// ==============================
document.getElementById('absenForm').addEventListener('submit', function(e) {
    // Validasi Dasar
    if (!latitude.value || !longitude.value) {
        e.preventDefault();
        notif.innerText="⚠️ Lokasi wajib diambil.";
        notif.classList.remove('hidden','bg-green-500');
        notif.classList.add('bg-red-500');
        return;
    }
    if (!fotoBase64.value || photoTaken.value !== '1' || faceConfirmed.value !== '1') {
        e.preventDefault();
        photoWarning.innerText = '⚠️ Harap ambil foto dengan wajah terdeteksi.';
        photoWarning.classList.remove('hidden');
        return;
    }
    if (desc.value.trim() === '') {
        e.preventDefault();
        alert('⚠️ Keterangan harus diisi!');
        return;
    }

    // JIKA VALIDASI BERHASIL
    // 1. Matikan tombol supaya tidak diklik 2 kali
    submitBtn.disabled = true;
    
    // 2. Ubah UI tombol menjadi "Sedang Mengirim..." beserta spinner
    submitBtn.innerHTML = '<span class="spinner"></span> Sedang Mengirim...';
    submitBtn.classList.remove('bg-emerald-800', 'hover:bg-emerald-700');
    submitBtn.classList.add('bg-emerald-900', 'cursor-wait');
    
    // 3. Hilangkan pesan error di bawahnya (jika ada)
    submitStatusMessage.textContent = 'Mohon tunggu sebentar, sedang mengirim data...';
    submitStatusMessage.style.color = '#047857'; // Warna hijau pesan proses
});

// ==============================
//  Cleanup
// ==============================
window.addEventListener('beforeunload', function() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    if (faceDetectionInterval) {
        clearInterval(faceDetectionInterval);
        faceDetectionInterval = null;
    }
});

// ==============================
//  Inisialisasi
// ==============================
console.log('📱 Halaman Absen Masuk dimuat');
setTimeout(() => {
    enableSubmitCheck();
    if (!faceapiLoaded && !faceapiLoading && !faceapiFallback) {
        loadFaceAPI();
    }
}, 500);
</script>

</body>
</html>