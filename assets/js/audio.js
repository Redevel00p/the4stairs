/**
 * GRAND 5 STAIRS HALL - CUSTOM JAZZ AUDIO PLAYER (MINIMALIST MODERN)
 * ------------------------------------------------------------------
 * Mengontrol fungsionalitas pemutar musik modern minimalis.
 * Sinkronisasi cover art, metadata lagu, timeline progress, dan kontrol volume.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Playlist lagu (6 track)
    const playlist = [
        {
            id: 0,
            title: "Alennya",
            artist: "Redevelop",
            src: "alennya.mp3",
            cover: "assets/img/alennya.png",
            duration: "03:54"
        },
        {
            id: 1,
            title: "Dorogoi Dlinnoyu",
            artist: "Dessy Dobreva",
            src: "dorogoi.mp3",
            cover: "assets/img/dorogoi.png",
            duration: "03:31"
        },
        {
            id: 2,
            title: "Fly me to the moon",
            artist: "The Jazz Woman",
            src: "flyme.mp3",
            cover: "assets/img/flyme.png",
            duration: "03:06"
        },
        {
            id: 3,
            title: "Tri Belikh Konya (Три белых коня)",
            artist: "Kvarto",
            src: "tribelikh.mp3",
            cover: "assets/img/tribelikh.png",
            duration: "03:04"
        },
        {
            id: 4,
            title: "Kaze ga Fuite",
            artist: "Redevelop",
            src: "kazega.mp3",
            cover: "assets/img/kazega.png",
            duration: "02:43"
        },
        {
            id: 5,
            title: "Pesenka Frontovogo Shofyora",
            artist: "Timur Vedernikov",
            src: "pesenka.mp3",
            cover: "assets/img/pesenka.png",
            duration: "02:55"
        }
    ];

    let currentTrackIndex = 0;
    const audio = new Audio();
    audio.volume = 0.8; // Default volume
    
    let isPlaying = false;
    let isMuted = false;
    let preMuteVolume = 0.8;
    
    // Status Simulasi Fallback
    let isSimulating = false;
    let simulatedCurrentTime = 0;
    let simulationInterval = null;

    // DOM Elements
    const playPauseBtn = document.getElementById('play-pause');
    const prevTrackBtn = document.getElementById('prev-track');
    const nextTrackBtn = document.getElementById('next-track');
    
    const coverArt = document.getElementById('cover-art');
    const trackTitle = document.getElementById('current-title');
    const trackArtist = document.getElementById('current-artist');
    const currentTimeEl = document.getElementById('current-time');
    const totalTimeEl = document.getElementById('total-time');
    
    const progressBar = document.getElementById('progress-bar');
    const progressFill = document.getElementById('progress-fill');
    const progressKnob = document.getElementById('progress-knob');
    const trackListItems = document.querySelectorAll('.track-item');
    
    const muteToggle = document.getElementById('mute-toggle');
    const volumeControl = document.getElementById('volume-control');

    // SVG paths untuk tombol mute/unmute
    const iconUnmuted = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
    </svg>`;
    const iconMuted = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
    </svg>`;

    // Inisialisasi track pertama
    loadTrack(currentTrackIndex);

    // Konversi durasi string "MM:SS" ke detik numerik
    function parseDurationToSeconds(str) {
        const parts = str.split(':');
        return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }

    // Format waktu detik ke format "MM:SS"
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    // Load track berdasarkan index
    function loadTrack(index) {
        currentTrackIndex = index;
        const track = playlist[index];
        
        audio.src = track.src;
        audio.load();
        
        trackTitle.textContent = track.title;
        trackArtist.textContent = track.artist;
        currentTimeEl.textContent = "00:00";
        totalTimeEl.textContent = track.duration;
        
        progressFill.style.width = "0%";
        progressKnob.style.left = "0%";
        
        simulatedCurrentTime = 0;
        
        // Update Cover Art
        if (coverArt) coverArt.src = track.cover;

        // Reset visualizer waves di playlist
        trackListItems.forEach(item => {
            item.classList.remove('active');
            const wave = item.querySelector('.playing-wave');
            if (wave) wave.classList.add('hidden');
            
            if (parseInt(item.dataset.index) === index) {
                item.classList.add('active');
                if (isPlaying && wave) {
                    wave.classList.remove('hidden');
                }
            }
        });
    }

    // Memutar musik
    function playTrack() {
        // Tampilkan gelombang suara di track list yang aktif
        const activeListItem = document.querySelector(`.track-item[data-index="${currentTrackIndex}"]`);
        if (activeListItem) {
            const wave = activeListItem.querySelector('.playing-wave');
            if (wave) wave.classList.remove('hidden');
        }

        audio.play().then(() => {
            isPlaying = true;
            isSimulating = false;
            stopSimulation();
            playPauseBtn.innerHTML = '&#10074;&#10074;'; // Simbol Pause
        }).catch(err => {
            // Jalankan mode simulasi visual jika autoplay diblokir atau file audio tidak dapat diakses
            console.log("Audio playback failed or blocked. Starting visual simulation fallback.");
            isPlaying = true;
            isSimulating = true;
            playPauseBtn.innerHTML = '&#10074;&#10074;';
            startSimulation();
        });
    }

    // Menghentikan musik
    function pauseTrack() {
        audio.pause();
        stopSimulation();
        
        isPlaying = false;
        playPauseBtn.innerHTML = '&#9658;'; // Simbol Play

        // Sembunyikan gelombang suara di playlist
        trackListItems.forEach(item => {
            const wave = item.querySelector('.playing-wave');
            if (wave) wave.classList.add('hidden');
        });
    }

    // Toggle Play / Pause
    function togglePlay() {
        if (isPlaying) {
            pauseTrack();
        } else {
            playTrack();
        }
    }

    // Track Selanjutnya
    function nextTrack() {
        let nextIndex = currentTrackIndex + 1;
        if (nextIndex >= playlist.length) {
            nextIndex = 0;
        }
        loadTrack(nextIndex);
        if (isPlaying) {
            playTrack();
        }
    }

    // Track Sebelumnya
    function prevTrack() {
        let prevIndex = currentTrackIndex - 1;
        if (prevIndex < 0) {
            prevIndex = playlist.length - 1;
        }
        loadTrack(prevIndex);
        if (isPlaying) {
            playTrack();
        }
    }

    // Memulai simulasi visual fallback
    function startSimulation() {
        stopSimulation();
        const durationSec = parseDurationToSeconds(playlist[currentTrackIndex].duration);
        
        simulationInterval = setInterval(() => {
            if (simulatedCurrentTime < durationSec) {
                simulatedCurrentTime += 1;
                const progressPercent = (simulatedCurrentTime / durationSec) * 100;
                
                // Update Timeline UI
                progressFill.style.width = `${progressPercent}%`;
                progressKnob.style.left = `${progressPercent}%`;
                currentTimeEl.textContent = formatTime(simulatedCurrentTime);
            } else {
                // Selesai, putar lagu selanjutnya
                nextTrack();
            }
        }, 1000);
    }

    // Menghentikan simulasi visual fallback
    function stopSimulation() {
        if (simulationInterval) {
            clearInterval(simulationInterval);
            simulationInterval = null;
        }
    }

    // Handle Volume slider
    function setVolume(val) {
        audio.volume = val;
        volumeControl.value = val;
        
        if (val == 0) {
            isMuted = true;
            muteToggle.innerHTML = iconMuted;
        } else {
            isMuted = false;
            muteToggle.innerHTML = iconUnmuted;
            preMuteVolume = val;
        }
    }

    // Toggle Mute
    function toggleMute() {
        if (isMuted) {
            setVolume(preMuteVolume);
        } else {
            preMuteVolume = audio.volume > 0 ? audio.volume : 0.8;
            setVolume(0);
        }
    }

    // Event Listeners
    playPauseBtn.addEventListener('click', togglePlay);
    prevTrackBtn.addEventListener('click', prevTrack);
    nextTrackBtn.addEventListener('click', nextTrack);
    muteToggle.addEventListener('click', toggleMute);

    // Event input volume slider
    volumeControl.addEventListener('input', (e) => {
        setVolume(parseFloat(e.target.value));
    });

    // Event click item playlist
    trackListItems.forEach(item => {
        item.addEventListener('click', () => {
            const index = parseInt(item.dataset.index);
            loadTrack(index);
            playTrack();
        });
    });

    // Sinkronisasi progress real audio playback
    audio.addEventListener('timeupdate', () => {
        if (isSimulating) return; // Abaikan jika sedang berjalan di mode simulasi
        
        const current = audio.currentTime;
        const duration = audio.duration || 0;
        
        if (duration > 0) {
            const progressPercent = (current / duration) * 100;
            progressFill.style.width = `${progressPercent}%`;
            progressKnob.style.left = `${progressPercent}%`;
            currentTimeEl.textContent = formatTime(current);
            totalTimeEl.textContent = formatTime(duration);
        }
    });

    // Auto-advance saat lagu real audio selesai
    audio.addEventListener('ended', () => {
        nextTrack();
        playTrack();
    });

    // Klik seek timeline bar
    progressBar.addEventListener('click', (e) => {
        const rect = progressBar.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const width = rect.width;
        const clickPercent = Math.max(0, Math.min(1, clickX / width));
        
        const durationSec = audio.duration || parseDurationToSeconds(playlist[currentTrackIndex].duration);
        const seekTime = clickPercent * durationSec;

        if (!isSimulating && audio.duration > 0) {
            audio.currentTime = seekTime;
        } else {
            // Simulasi seek
            simulatedCurrentTime = Math.floor(seekTime);
            progressFill.style.width = `${clickPercent * 100}%`;
            progressKnob.style.left = `${clickPercent * 100}%`;
            currentTimeEl.textContent = formatTime(simulatedCurrentTime);
        }
    });
});
