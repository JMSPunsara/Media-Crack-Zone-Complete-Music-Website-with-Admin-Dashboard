</div>
    </main>

    <!-- Music Player -->
    <div class="music-player" id="musicPlayer">
        <button class="player-close-btn" id="closePlayerBtn" title="Close Player">
            <i class="fas fa-times"></i>
        </button>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <img src="" alt="Now Playing" class="track-cover me-3" id="playerCover" style="width: 60px; height: 60px;">
                        <div class="flex-grow-1">
                            <div class="track-title" id="playerTitle">No track selected</div>
                            <div class="track-artist" id="playerArtist">-</div>
                            <div class="player-status" id="playerStatus" style="font-size: 0.75rem; color: var(--text-light); display: none;">
                                <i class="fas fa-sync-alt me-1"></i>Synced across pages
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="player-controls">
                        <button class="action-btn" id="prevBtn">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button class="play-btn" id="playPauseBtn">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="action-btn" id="nextBtn">
                            <i class="fas fa-step-forward"></i>
                        </button>
                    </div>
                    <div class="progress-container mt-2">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span id="currentTime">0:00</span>
                            <span id="totalTime">0:00</span>
                        </div>
                        <div class="progress-bar" id="progressBar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center justify-content-end">
                        <button class="action-btn me-2" id="favoriteBtn" title="Add to Favorites">
                            <i class="far fa-heart" id="favoriteIcon"></i>
                        </button>
                        <button class="action-btn me-2" id="downloadBtn" title="Download Song">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="action-btn me-2" id="shuffleBtn">
                            <i class="fas fa-random"></i>
                        </button>
                        <button class="action-btn me-2" id="repeatBtn">
                            <i class="fas fa-redo"></i>
                        </button>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-volume-up me-2"></i>
                            <input type="range" class="form-range" min="0" max="100" value="80" id="volumeSlider" style="width: 100px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Element -->
    <audio id="audioPlayer" preload="metadata"></audio>

    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Music Player State
        let currentTrack = null;
        let isPlaying = false;
        let playlist = [];
        let currentTrackIndex = 0;
        let isShuffled = false;
        let repeatMode = 0; // 0: no repeat, 1: repeat all, 2: repeat one

        // DOM Elements
        const audioPlayer = document.getElementById('audioPlayer');
        const musicPlayer = document.getElementById('musicPlayer');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const shuffleBtn = document.getElementById('shuffleBtn');
        const repeatBtn = document.getElementById('repeatBtn');
        const favoriteBtn = document.getElementById('favoriteBtn');
        const favoriteIcon = document.getElementById('favoriteIcon');
        const downloadBtn = document.getElementById('downloadBtn');
        const closePlayerBtn = document.getElementById('closePlayerBtn');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const volumeSlider = document.getElementById('volumeSlider');
        const currentTime = document.getElementById('currentTime');
        const totalTime = document.getElementById('totalTime');
        const playerTitle = document.getElementById('playerTitle');
        const playerArtist = document.getElementById('playerArtist');
        const playerCover = document.getElementById('playerCover');
        const searchInput = document.getElementById('searchInput');

        // Cross-page music state manager
        class MusicStateManager {
            constructor() {
                this.storageKey = 'musicPlayerState';
                this.heartbeatInterval = null;
                this.lastHeartbeat = Date.now();
            }
            
            // Save complete player state
            saveState() {
                if (!currentTrack) return;
                
                const state = {
                    track: currentTrack,
                    playlist: playlist,
                    currentIndex: currentTrackIndex,
                    isPlaying: isPlaying && !audioPlayer.paused,
                    currentTime: audioPlayer.currentTime || 0,
                    volume: audioPlayer.volume || 0.8,
                    isShuffled: isShuffled,
                    repeatMode: repeatMode,
                    enableContinuous: window.enableContinuousPlay,
                    timestamp: Date.now(),
                    pageUrl: window.location.href,
                    sessionId: this.getSessionId()
                };
                
                localStorage.setItem(this.storageKey, JSON.stringify(state));
                this.updateHeartbeat();
            }
            
            // Restore player state with validation
            restoreState() {
                try {
                    const savedState = localStorage.getItem(this.storageKey);
                    if (!savedState) return false;
                    
                    const state = JSON.parse(savedState);
                    
                    // Validate state freshness (max 2 hours)
                    const maxAge = 2 * 60 * 60 * 1000;
                    if (Date.now() - state.timestamp > maxAge) {
                        this.clearState();
                        return false;
                    }
                    
                    // Restore all state
                    currentTrack = state.track;
                    playlist = state.playlist || [];
                    currentTrackIndex = state.currentIndex || 0;
                    isShuffled = state.isShuffled || false;
                    repeatMode = state.repeatMode || 0;
                    window.enableContinuousPlay = state.enableContinuous !== false;
                    
                    // Update UI
                    this.updatePlayerUI(state);
                    
                    console.log('🔄 Player state restored successfully');
                    return true;
                    
                } catch (error) {
                    console.error('❌ Error restoring player state:', error);
                    this.clearState();
                    return false;
                }
            }
            
            // Update player UI with restored state
            updatePlayerUI(state) {
                if (!state.track) return;
                
                // Set track info
                playerTitle.textContent = state.track.title || 'Unknown Title';
                playerArtist.textContent = state.track.artist || 'Unknown Artist';
                playerCover.src = state.track.cover_image || '<?php echo UPLOAD_URL; ?>covers/default-cover.jpg';
                
                // Set audio source
                let audioSrc;
                if (state.track.file_path.startsWith('http')) {
                    audioSrc = state.track.file_path;
                } else {
                    const cleanPath = state.track.file_path.replace(/^(uploads\/music\/|music\/|\/)/g, '');
                    audioSrc = `<?php echo UPLOAD_URL; ?>music/${cleanPath}`;
                }
                audioPlayer.src = audioSrc;
                
                // Set volume
                if (state.volume) {
                    audioPlayer.volume = state.volume;
                    volumeSlider.value = state.volume * 100;
                }
                
                // Update control states
                shuffleBtn.classList.toggle('text-success', isShuffled);
                updateRepeatButton();
                updateFavoriteButton(state.track.id);
                
                // Show player
                musicPlayer.style.display = 'block';
                
                // Show restoration notification
                const statusIndicator = document.getElementById('playerStatus');
                if (statusIndicator) {
                    statusIndicator.style.display = 'block';
                    setTimeout(() => {
                        statusIndicator.style.display = 'none';
                    }, 3000);
                }
                
                // Restore playback position and state
                audioPlayer.addEventListener('loadeddata', () => {
                    audioPlayer.currentTime = state.currentTime || 0;
                    
                    if (state.isPlaying) {
                        audioPlayer.play().then(() => {
                            isPlaying = true;
                            playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                            showToast('🎵 Music resumed across pages!', 'success');
                        }).catch(error => {
                            console.log('Auto-resume blocked:', error);
                            isPlaying = false;
                            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                            showToast('Click play to resume music', 'info');
                        });
                    } else {
                        isPlaying = false;
                        playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                        showToast('Music player restored', 'info');
                    }
                }, { once: true });
                
                // Load the audio
                audioPlayer.load();
            }
            
            // Get or create session ID
            getSessionId() {
                let sessionId = sessionStorage.getItem('musicSessionId');
                if (!sessionId) {
                    sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    sessionStorage.setItem('musicSessionId', sessionId);
                }
                return sessionId;
            }
            
            // Update heartbeat for cross-tab sync
            updateHeartbeat() {
                this.lastHeartbeat = Date.now();
                localStorage.setItem('musicHeartbeat', this.lastHeartbeat.toString());
            }
            
            // Start heartbeat monitoring
            startHeartbeat() {
                this.heartbeatInterval = setInterval(() => {
                    if (currentTrack && isPlaying) {
                        this.saveState();
                    }
                }, 2000); // Save state every 2 seconds when playing
            }
            
            // Stop heartbeat monitoring
            stopHeartbeat() {
                if (this.heartbeatInterval) {
                    clearInterval(this.heartbeatInterval);
                    this.heartbeatInterval = null;
                }
            }
            
            // Clear state
            clearState() {
                localStorage.removeItem(this.storageKey);
                localStorage.removeItem('musicHeartbeat');
            }
            
            // Check if another tab is controlling music
            isOtherTabActive() {
                const lastHeartbeat = localStorage.getItem('musicHeartbeat');
                if (!lastHeartbeat) return false;
                
                const timeDiff = Date.now() - parseInt(lastHeartbeat);
                return timeDiff < 5000; // Consider active if heartbeat within 5 seconds
            }
        }
        
        // Initialize state manager
        const stateManager = new MusicStateManager();

        // Enhanced initialization with state manager
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎵 Initializing enhanced music player...');
            
            // Set initial volume
            audioPlayer.volume = 0.8;
            
            // Load saved volume
            const savedVolume = localStorage.getItem('musicPlayerVolume');
            if (savedVolume) {
                audioPlayer.volume = savedVolume / 100;
                volumeSlider.value = savedVolume;
            }
            
            // Detect audio format support
            detectAudioSupport();
            
            // Enhanced audio event listeners
            audioPlayer.addEventListener('loadstart', () => {
                console.log('🔄 Audio loading started');
            });
            
            audioPlayer.addEventListener('loadedmetadata', () => {
                console.log('✅ Audio metadata loaded');
                stateManager.saveState();
            });
            
            audioPlayer.addEventListener('canplay', () => {
                console.log('✅ Audio ready to play');
            });
            
            audioPlayer.addEventListener('error', (e) => {
                console.error('❌ Audio error:', e, audioPlayer.error);
                const errorMessages = {
                    1: 'Audio loading was aborted',
                    2: 'Network error occurred - check your connection',
                    3: 'Audio file is corrupted or cannot be decoded',
                    4: 'Audio format not supported by your browser'
                };
                const errorMsg = errorMessages[audioPlayer.error?.code] || 'Unknown audio error';
                showToast(errorMsg, 'error');
                
                // Try to reload with different format
                if (currentTrack && audioPlayer.error?.code === 4) {
                    console.log('🔄 Trying alternative audio format...');
                    setTimeout(() => {
                        playTrack(currentTrack);
                    }, 1000);
                }
            });
            
            audioPlayer.addEventListener('stalled', () => {
                console.warn('⚠️ Audio playback stalled');
                showToast('Audio loading stalled, please wait...', 'warning');
            });
            
            audioPlayer.addEventListener('waiting', () => {
                console.log('⏳ Audio buffering...');
            });
            
            audioPlayer.addEventListener('playing', () => {
                console.log('▶️ Audio playing');
                isPlaying = true;
                playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                stateManager.saveState();
            });
            
            audioPlayer.addEventListener('pause', () => {
                console.log('⏸️ Audio paused');
                isPlaying = false;
                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                stateManager.saveState();
            });
            
            // Start state manager
            stateManager.startHeartbeat();
            
            // Restore player state from localStorage
            const restored = stateManager.restoreState();
            if (!restored) {
                console.log('ℹ️ No previous player state found');
            }
            
            // Enable continuous play by default
            window.enableContinuousPlay = true;
            
            console.log('✅ Enhanced music player initialized successfully');
        });

        // Enhanced save player state using state manager
        function savePlayerState() {
            stateManager.saveState();
        }

        // Legacy restore function - now handled by state manager
        function restorePlayerState() {
            return stateManager.restoreState();
        }

        // Update repeat button visual state
        function updateRepeatButton() {
            repeatBtn.classList.remove('text-success', 'text-warning');
            if (repeatMode === 1) {
                repeatBtn.classList.add('text-success');
                repeatBtn.innerHTML = '<i class="fas fa-redo"></i>';
            } else if (repeatMode === 2) {
                repeatBtn.classList.add('text-warning');
                repeatBtn.innerHTML = '<i class="fas fa-redo-alt"></i>';
            } else {
                repeatBtn.innerHTML = '<i class="fas fa-redo"></i>';
            }
        }

        // Enhanced close player function
        function closePlayer() {
            console.log('🔽 Closing music player...');
            
            // If music is playing, ask for confirmation
            if (!audioPlayer.paused && currentTrack) {
                const confirmClose = confirm('Music is currently playing. Are you sure you want to close the player?');
                if (!confirmClose) {
                    return; // Don't close if user cancels
                }
            }
            
            // Stop state manager
            stateManager.stopHeartbeat();
            
            // Pause and stop audio
            audioPlayer.pause();
            audioPlayer.currentTime = 0;
            audioPlayer.src = '';
            
            // Hide the player with animation
            musicPlayer.style.opacity = '0';
            musicPlayer.style.transform = 'translateY(100%)';
            
            setTimeout(() => {
                musicPlayer.style.display = 'none';
                musicPlayer.style.opacity = '';
                musicPlayer.style.transform = '';
            }, 300);
            
            // Clear current track and states
            currentTrack = null;
            currentTrackIndex = 0;
            playlist = [];
            isPlaying = false;
            
            // Reset UI
            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            playerTitle.textContent = 'No track selected';
            playerArtist.textContent = '-';
            playerCover.src = '';
            progressFill.style.width = '0%';
            currentTime.textContent = '0:00';
            totalTime.textContent = '0:00';
            
            // Reset shuffle and repeat states
            isShuffled = false;
            repeatMode = 0;
            shuffleBtn.classList.remove('text-success');
            updateRepeatButton();
            
            // Clear saved state
            stateManager.clearState();
            
            // Hide player status
            const playerStatus = document.getElementById('playerStatus');
            if (playerStatus) {
                playerStatus.style.display = 'none';
            }
            
            // Show close notification
            showToast('Music player closed', 'info');
            
            console.log('✅ Music player closed successfully');
        }

        // Enhanced play track function with better error handling
        function playTrack(trackData) {
            console.log('🎵 Attempting to play track:', trackData);
            
            if (!trackData || !trackData.file_path) {
                console.error('❌ Invalid track data:', trackData);
                showToast('Invalid track data', 'error');
                return;
            }
            
            currentTrack = trackData;
            
            // Enhanced audio source handling with multiple fallbacks
            let audioSrc;
            if (trackData.file_path.startsWith('http')) {
                // Already a full URL
                audioSrc = trackData.file_path;
            } else if (trackData.file_path.startsWith('<?php echo UPLOAD_URL; ?>')) {
                // Already has upload URL prefix
                audioSrc = trackData.file_path;
            } else {
                // Clean file path and construct full URL
                const cleanPath = trackData.file_path.replace(/^(uploads\/music\/|music\/|\/)/g, '');
                audioSrc = `<?php echo UPLOAD_URL; ?>music/${cleanPath}`;
            }
            
            console.log('🔗 Constructed audio source URL:', audioSrc);
            console.log('📊 Original file_path:', trackData.file_path);
            
            // Clear previous audio and reset
            audioPlayer.pause();
            audioPlayer.currentTime = 0;
            audioPlayer.src = '';
            
            // Test if audio URL is accessible before setting
            console.log('🔍 Testing audio URL accessibility...');
            fetch(audioSrc, { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        console.log('✅ Audio URL is accessible');
                    } else {
                        console.warn('⚠️ Audio URL returned status:', response.status);
                    }
                })
                .catch(error => {
                    console.warn('⚠️ Audio URL test failed:', error);
                });
            
            // Enhanced audio loading with better error handling
            audioPlayer.src = audioSrc;
            
            // Update UI immediately
            playerTitle.textContent = trackData.title || 'Unknown Title';
            playerArtist.textContent = trackData.artist || 'Unknown Artist';
            playerCover.src = trackData.cover_image || '<?php echo UPLOAD_URL; ?>covers/default-cover.jpg';
            
            // Show player
            musicPlayer.style.display = 'block';
            
            // Update favorite button status
            updateFavoriteButton(trackData.id);
            
            // Enhanced audio loading with promises and better error handling
            return new Promise((resolve, reject) => {
                console.log('🔄 Loading audio...');
                
                // Set up one-time event listeners for this load
                const handleLoadedData = () => {
                    console.log('✅ Audio metadata loaded successfully');
                    cleanup();
                    resolve();
                };
                
                const handleCanPlay = () => {
                    console.log('✅ Audio can start playing');
                };
                
                const handleError = (error) => {
                    console.error('❌ Audio loading error:', error, audioPlayer.error);
                    cleanup();
                    
                    let errorMessage = 'Unknown audio error';
                    if (audioPlayer.error) {
                        switch(audioPlayer.error.code) {
                            case 1: errorMessage = 'Audio loading was aborted'; break;
                            case 2: errorMessage = 'Network error - check your connection'; break;
                            case 3: errorMessage = 'Audio file is corrupted or invalid'; break;
                            case 4: errorMessage = 'Audio format not supported by your browser'; break;
                        }
                    }
                    
                    showToast(`Playback Error: ${errorMessage}`, 'error');
                    reject(new Error(errorMessage));
                };
                
                const handleLoadStart = () => {
                    console.log('🔄 Audio loading started...');
                };
                
                const cleanup = () => {
                    audioPlayer.removeEventListener('loadeddata', handleLoadedData);
                    audioPlayer.removeEventListener('canplay', handleCanPlay);
                    audioPlayer.removeEventListener('error', handleError);
                    audioPlayer.removeEventListener('loadstart', handleLoadStart);
                };
                
                // Add event listeners
                audioPlayer.addEventListener('loadeddata', handleLoadedData);
                audioPlayer.addEventListener('canplay', handleCanPlay);
                audioPlayer.addEventListener('error', handleError);
                audioPlayer.addEventListener('loadstart', handleLoadStart);
                
                // Force reload and load
                audioPlayer.load();
                
                // Timeout for loading
                setTimeout(() => {
                    if (audioPlayer.readyState < 2) {
                        console.warn('⚠️ Audio loading timeout');
                        cleanup();
                        reject(new Error('Audio loading timeout'));
                    }
                }, 10000); // 10 second timeout
            })
            .then(() => {
                // Successfully loaded, now try to play
                return audioPlayer.play();
            })
            .then(() => {
                console.log('🎵 Audio playback started successfully');
                isPlaying = true;
                playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                
                // Save player state
                savePlayerState();
                
                // Update play count and history
                updatePlayStatistics(trackData);
                
                showToast(`Now playing: ${trackData.title}`, 'success');
                
            })
            .catch(error => {
                console.error('❌ Playback failed:', error);
                isPlaying = false;
                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                
                // Try alternative audio source if available
                if (!audioSrc.includes('.mp3') && trackData.file_path.includes('.')) {
                    console.log('🔄 Trying with .mp3 extension...');
                    const alternativeSource = audioSrc.replace(/\.[^/.]+$/, '.mp3');
                    audioPlayer.src = alternativeSource;
                    audioPlayer.load();
                    audioPlayer.play().catch(() => {
                        showToast('Unable to play this track. Format may not be supported.', 'error');
                    });
                } else {
                    showToast(`Cannot play track: ${error.message}`, 'error');
                }
            });
        }
        
        // Update play statistics (play count and history)
        function updatePlayStatistics(trackData) {
            if (!trackData.id) return;
            
            // Update play count
            fetch('<?php echo SITE_URL; ?>/api/update_play_count.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ track_id: trackData.id })
            }).catch(error => {
                console.log('Play count update failed:', error);
            });
            
            // Record play history (only for logged-in users)
            <?php if (isLoggedIn()): ?>
            fetch('<?php echo SITE_URL; ?>/api/record_play.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ track_id: trackData.id })
            }).catch(error => {
                console.log('Play history recording failed:', error);
            });
            <?php endif; ?>
        }

        // Enhanced Play/Pause toggle with better error handling
        playPauseBtn.addEventListener('click', function() {
            if (!currentTrack) {
                showToast('No track selected', 'warning');
                return;
            }
            
            if (isPlaying) {
                console.log('⏸️ Pausing audio...');
                audioPlayer.pause();
                isPlaying = false;
                this.innerHTML = '<i class="fas fa-play"></i>';
                console.log('✅ Audio paused');
                savePlayerState();
            } else {
                console.log('▶️ Resuming audio...');
                
                // Check if audio source is still valid
                if (!audioPlayer.src || audioPlayer.error) {
                    console.log('🔄 Reloading audio source...');
                    playTrack(currentTrack);
                    return;
                }
                
                audioPlayer.play().then(() => {
                    isPlaying = true;
                    this.innerHTML = '<i class="fas fa-pause"></i>';
                    console.log('✅ Audio resumed successfully');
                    savePlayerState();
                }).catch(error => {
                    console.error('❌ Error resuming playback:', error);
                    
                    // Try to reload the track
                    console.log('🔄 Attempting to reload track...');
                    playTrack(currentTrack);
                });
            }
        });

        // Enhanced Previous track with playlist support
        prevBtn.addEventListener('click', function() {
            console.log('⏮️ Previous track requested');
            if (playlist.length > 0) {
                currentTrackIndex = (currentTrackIndex - 1 + playlist.length) % playlist.length;
                console.log(`Playing track ${currentTrackIndex + 1} of ${playlist.length}`);
                playTrack(playlist[currentTrackIndex]);
            } else {
                showToast('No playlist available', 'warning');
            }
        });

        // Enhanced Next track with automatic continuation
        nextBtn.addEventListener('click', function() {
            console.log('⏭️ Next track requested');
            playNextTrack();
        });
        
        // Enhanced next track function with auto-loading
        function playNextTrack() {
            if (playlist.length > 0) {
                if (currentTrackIndex < playlist.length - 1) {
                    // Play next track in current playlist
                    currentTrackIndex++;
                    console.log(`Playing track ${currentTrackIndex + 1} of ${playlist.length}`);
                    playTrack(playlist[currentTrackIndex]);
                } else {
                    // End of playlist - try to load more similar tracks
                    console.log('📋 End of playlist reached');
                    if (window.enableContinuousPlay && currentTrack) {
                        console.log('🔄 Loading more similar tracks...');
                        loadMoreTracksAndContinue();
                    } else {
                        // Just loop back to beginning if repeat is on
                        if (repeatMode === 1) {
                            currentTrackIndex = 0;
                            playTrack(playlist[currentTrackIndex]);
                        } else {
                            showToast('End of playlist reached', 'info');
                            isPlaying = false;
                            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                            savePlayerState();
                        }
                    }
                }
            } else {
                showToast('No playlist available', 'warning');
            }
        }

        // Shuffle toggle
        shuffleBtn.addEventListener('click', function() {
            isShuffled = !isShuffled;
            this.classList.toggle('text-success', isShuffled);
            if (isShuffled) {
                shufflePlaylist();
            }
            // Save state after shuffle change
            savePlayerState();
        });

        // Repeat toggle
        repeatBtn.addEventListener('click', function() {
            repeatMode = (repeatMode + 1) % 3;
            updateRepeatButton();
            // Save state after repeat change
            savePlayerState();
        });

        // Close player button
        closePlayerBtn.addEventListener('click', function() {
            console.log('Close player button clicked');
            closePlayer();
        });

        // Add keyboard shortcut to close player (Escape key)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && musicPlayer.style.display !== 'none' && currentTrack) {
                console.log('Escape key pressed - closing player');
                closePlayer();
            }
        });

        // Volume control
        volumeSlider.addEventListener('input', function() {
            audioPlayer.volume = this.value / 100;
            localStorage.setItem('musicPlayerVolume', this.value);
            // Save state when volume changes
            savePlayerState();
        });

        // Progress bar update and periodic state saving
        audioPlayer.addEventListener('timeupdate', function() {
            if (audioPlayer.duration) {
                const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
                progressFill.style.width = progress + '%';
                currentTime.textContent = formatTime(audioPlayer.currentTime);
                totalTime.textContent = formatTime(audioPlayer.duration);
                
                // Save state every 5 seconds during playback
                if (Math.floor(audioPlayer.currentTime) % 5 === 0) {
                    savePlayerState();
                }
            }
        });

        // Progress bar click
        progressBar.addEventListener('click', function(e) {
            if (audioPlayer.duration) {
                const rect = this.getBoundingClientRect();
                const percent = (e.clientX - rect.left) / rect.width;
                audioPlayer.currentTime = percent * audioPlayer.duration;
                // Save state after seeking
                savePlayerState();
            }
        });

        // Save state when audio loads
        audioPlayer.addEventListener('loadeddata', function() {
            if (currentTrack) {
                savePlayerState();
            }
        });

        // Save state when playback starts
        audioPlayer.addEventListener('play', function() {
            isPlaying = true;
            savePlayerState();
        });

        // Save state when playback pauses
        audioPlayer.addEventListener('pause', function() {
            isPlaying = false;
            savePlayerState();
        });

        // Enhanced track ended event with smart continuation
        audioPlayer.addEventListener('ended', function() {
            console.log('🔚 Track ended:', currentTrack?.title);
            
            if (repeatMode === 2) {
                // Repeat current track
                console.log('🔁 Repeating current track');
                audioPlayer.currentTime = 0;
                audioPlayer.play().catch(error => {
                    console.error('Error repeating track:', error);
                    playTrack(currentTrack); // Reload if needed
                });
            } else {
                // Play next track automatically
                console.log('⏭️ Auto-playing next track');
                playNextTrack();
            }
        });
        
        // Enhanced continuous play function
        function loadMoreTracksAndContinue() {
            if (!currentTrack) {
                console.log('❌ No current track for similarity matching');
                return;
            }
            
            console.log('🔍 Loading similar tracks for:', currentTrack.title);
            showToast('Loading similar tracks...', 'info');
            
            // Determine filters based on current track
            const filters = {
                language_id: currentTrack.language_id,
                mood_id: currentTrack.mood_id
            };
            
            // Get already played track IDs to exclude
            const excludePlayed = playlist.map(t => t.id);
            
            fetch('<?php echo SITE_URL; ?>/api/get_similar_tracks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    track_id: currentTrack.id,
                    filters: filters,
                    exclude_played: excludePlayed
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('📦 Similar tracks response:', data);
                
                if (data.success && data.tracks && data.tracks.length > 0) {
                    // Add new tracks to playlist
                    const newTracks = data.tracks.slice(0, 10); // Limit to 10 new tracks
                    playlist.push(...newTracks);
                    
                    console.log(`✅ Added ${newTracks.length} similar tracks to playlist`);
                    
                    // Continue to next track
                    currentTrackIndex++;
                    playTrack(playlist[currentTrackIndex]);
                    
                    showToast(`Added ${newTracks.length} similar tracks`, 'success');
                } else {
                    console.log('❌ No more similar tracks found');
                    
                    // Try to get random tracks as fallback
                    loadRandomTracksAsFallback();
                }
            })
            .catch(error => {
                console.error('❌ Error loading similar tracks:', error);
                loadRandomTracksAsFallback();
            });
        }
        
        // Fallback to random tracks when no similar tracks found
        function loadRandomTracksAsFallback() {
            console.log('🎲 Loading random tracks as fallback...');
            
            fetch('<?php echo SITE_URL; ?>/api/get_filtered_tracks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    filters: {},
                    limit: 10,
                    random: true,
                    exclude_played: playlist.map(t => t.id)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tracks && data.tracks.length > 0) {
                    const newTracks = data.tracks;
                    playlist.push(...newTracks);
                    
                    currentTrackIndex++;
                    playTrack(playlist[currentTrackIndex]);
                    
                    console.log(`✅ Added ${newTracks.length} random tracks`);
                    showToast(`Playing random tracks`, 'info');
                } else {
                    console.log('❌ No more tracks available');
                    isPlaying = false;
                    playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                    showToast('No more tracks available', 'warning');
                    savePlayerState();
                }
            })
            .catch(error => {
                console.error('❌ Error loading random tracks:', error);
                isPlaying = false;
                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                showToast('Unable to load more tracks', 'error');
                savePlayerState();
            });
        }

        // Search functionality
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length > 2) {
                    searchTimeout = setTimeout(() => {
                        performSearch(query);
                    }, 300);
                }
            });
        }

        // Search function
        function performSearch(query) {
            fetch('<?php echo SITE_URL; ?>/api/search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            })
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
            });
        }

        // Enhanced continuous playback
        let currentFilters = {
            language: null,
            mood: null,
            search: null
        };
        
        window.enableContinuousPlay = true;
        
        function loadMoreTracksAndContinue() {
            if (!currentTrack) return;
            
            // Determine filters based on current track
            const filters = {
                language_id: currentTrack.language_id,
                mood_id: currentTrack.mood_id
            };
            
            fetch('<?php echo SITE_URL; ?>/api/get_similar_tracks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    track_id: currentTrack.id,
                    filters: filters,
                    exclude_played: playlist.map(t => t.id)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tracks.length > 0) {
                    // Add new tracks to playlist
                    playlist.push(...data.tracks);
                    // Continue to next track
                    currentTrackIndex++;
                    playTrack(playlist[currentTrackIndex]);
                    
                    // Show notification
                    showNotification('Loading similar tracks...', 'info');
                } else {
                    // No more similar tracks found
                    isPlaying = false;
                    playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                    showNotification('No more similar tracks found', 'warning');
                }
            })
            .catch(error => {
                console.error('Error loading more tracks:', error);
                isPlaying = false;
                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            });
        }
        
        // Enhanced add to favorites with UI feedback
        function addToFavorites(trackId) {
            <?php if (isLoggedIn()): ?>
            fetch('<?php echo SITE_URL; ?>/api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ track_id: trackId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI to reflect favorite status
                    document.querySelectorAll(`[data-track-id="${trackId}"] .favorite-btn`).forEach(btn => {
                        btn.classList.toggle('favorited', data.is_favorite);
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('fas', data.is_favorite);
                            icon.classList.toggle('far', !data.is_favorite);
                        }
                    });
                    
                    // Show notification
                    const message = data.is_favorite ? 'Added to favorites' : 'Removed from favorites';
                    showNotification(message, data.is_favorite ? 'success' : 'info');
                } else {
                    showNotification('Error updating favorites', 'error');
                }
            })
            .catch(error => {
                console.error('Error toggling favorite:', error);
                showNotification('Error updating favorites', 'error');
            });
            <?php else: ?>
            showNotification('Please login to add favorites', 'warning');
            <?php endif; ?>
        }
        
        // Enhanced playlist functions
        function createPlaylistFromFilters(language_id = null, mood_id = null, search = null) {
            const filters = { language_id, mood_id, search };
            
            fetch('<?php echo SITE_URL; ?>/api/get_filtered_tracks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ filters: filters, limit: 50 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tracks.length > 0) {
                    // Store current filters for continuous play
                    currentFilters = { language: language_id, mood: mood_id, search: search };
                    
                    // Set playlist and start playing
                    window.setPlaylist(data.tracks, 0);
                    showNotification(`Playing ${data.tracks.length} tracks`, 'success');
                } else {
                    showNotification('No tracks found with these filters', 'warning');
                }
            })
            .catch(error => {
                console.error('Error creating playlist:', error);
                showNotification('Error loading tracks', 'error');
            });
        }
        
        // Quick playlist creation functions
        window.playLanguage = function(language_id) {
            createPlaylistFromFilters(language_id, null, null);
        };
        
        window.playMood = function(mood_id) {
            createPlaylistFromFilters(null, mood_id, null);
        };
        
        window.playSearchResults = function(search_term) {
            createPlaylistFromFilters(null, null, search_term);
        };
        
        // Notification system
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            document.querySelectorAll('.music-notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `music-notification alert alert-${type === 'error' ? 'danger' : type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideInRight 0.3s ease;
            `;
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation' : 'info'} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 3000);
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            // Remove existing toast if any
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) {
                existingToast.remove();
            }

            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas ${getToastIcon(type)}"></i>
                    <span>${message}</span>
                </div>
            `;

            // Add to body
            document.body.appendChild(toast);

            // Show toast
            setTimeout(() => toast.classList.add('show'), 100);

            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function getToastIcon(type) {
            switch(type) {
                case 'success': return 'fa-check-circle';
                case 'error': return 'fa-exclamation-circle';
                case 'warning': return 'fa-exclamation-triangle';
                default: return 'fa-info-circle';
            }
        }

        // Utility functions
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        function shufflePlaylist() {
            if (playlist.length > 1) {
                const currentTrackData = playlist[currentTrackIndex];
                for (let i = playlist.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [playlist[i], playlist[j]] = [playlist[j], playlist[i]];
                }
                currentTrackIndex = playlist.findIndex(track => track.id === currentTrackData.id);
            }
        }

        // Global function to play track from any page
        window.playTrackGlobal = function(trackData) {
            console.log('🎵 playTrackGlobal called with:', trackData);
            playTrack(trackData);
        };

        // Global function to set playlist
        window.setPlaylist = function(tracks, startIndex = 0) {
            console.log('🎵 setPlaylist called with:', {
                tracksCount: tracks ? tracks.length : 0,
                startIndex: startIndex,
                firstTrack: tracks && tracks[0] ? tracks[0].title : 'N/A'
            });
            
            if (!tracks || !Array.isArray(tracks) || tracks.length === 0) {
                console.error('❌ Invalid tracks array provided to setPlaylist');
                showToast('No tracks available to play', 'warning');
                return;
            }
            
            if (startIndex >= tracks.length || startIndex < 0) {
                console.error('❌ Invalid start index:', startIndex);
                startIndex = 0;
            }
            
            playlist = tracks;
            currentTrackIndex = startIndex;
            
            console.log('✅ Playing track at index:', startIndex, tracks[startIndex]);
            playTrack(tracks[startIndex]);
            
            // Save state when new playlist is set
            if (typeof stateManager !== 'undefined') {
                stateManager.saveState();
            }
        };

        // Make functions available immediately
        console.log('🎵 Global player functions registered');
        
        // Debug function to check if everything is working
        window.debugMusicPlayer = function() {
            console.log('🔍 Music Player Debug Info:');
            console.log('- playTrackGlobal available:', typeof window.playTrackGlobal === 'function');
            console.log('- setPlaylist available:', typeof window.setPlaylist === 'function');
            console.log('- Current track:', currentTrack?.title || 'None');
            console.log('- Playlist length:', playlist?.length || 0);
            console.log('- Audio element:', audioPlayer ? 'Present' : 'Missing');
            console.log('- Player visible:', musicPlayer?.style.display !== 'none');
        };

        // Favorite button functionality
        favoriteBtn.addEventListener('click', function() {
            if (!currentTrack) {
                showToast('No track is currently playing', 'warning');
                return;
            }

            <?php if (isLoggedIn()): ?>
            const isFavorited = favoriteIcon.classList.contains('fas');
            
            fetch('<?php echo SITE_URL; ?>/api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ track_id: currentTrack.id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.is_favorite) {
                        favoriteIcon.className = 'fas fa-heart';
                        favoriteBtn.style.color = '#ff4757';
                        showToast('Added to favorites', 'success');
                    } else {
                        favoriteIcon.className = 'far fa-heart';
                        favoriteBtn.style.color = '';
                        showToast('Removed from favorites', 'info');
                    }
                } else {
                    showToast('Error updating favorites', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error updating favorites', 'error');
            });
            <?php else: ?>
            showToast('Please login to add favorites', 'warning');
            <?php endif; ?>
        });

        // Download button functionality
        downloadBtn.addEventListener('click', function() {
            if (!currentTrack) {
                showToast('No track is currently playing', 'warning');
                return;
            }

            // Show download confirmation
            if (confirm(`Download "${currentTrack.title}" by ${currentTrack.artist}?`)) {
                const downloadUrl = `<?php echo SITE_URL; ?>/api/download.php?track_id=${currentTrack.id}`;
                
                // Create temporary download link
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = `${currentTrack.artist} - ${currentTrack.title}.mp3`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showToast('Download started', 'success');
            }
        });

        // Update favorite button status
        function updateFavoriteButton(trackId) {
            if (!trackId) return;
            
            <?php if (isLoggedIn()): ?>
            fetch('<?php echo SITE_URL; ?>/api/check_favorite.php?track_id=' + trackId)
            .then(response => response.json())
            .then(data => {
                if (data.is_favorite) {
                    favoriteIcon.className = 'fas fa-heart';
                    favoriteBtn.style.color = '#ff4757';
                } else {
                    favoriteIcon.className = 'far fa-heart';
                    favoriteBtn.style.color = '';
                }
            })
            .catch(error => {
                console.error('Error checking favorite status:', error);
            });
            <?php endif; ?>
        }

        // Enhanced page lifecycle management
        window.addEventListener('beforeunload', function() {
            console.log('📤 Page unloading - saving state...');
            stateManager.saveState();
        });

        // Save state when page becomes hidden (mobile/tab switching)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                console.log('👁️ Page hidden - saving state...');
                stateManager.saveState();
            } else {
                console.log('👁️ Page visible - checking state...');
                // Check if another tab took over
                if (stateManager.isOtherTabActive() && isPlaying) {
                    // Another tab is controlling, pause this one
                    audioPlayer.pause();
                    showToast('Music is playing in another tab', 'info');
                }
            }
        });

        // Focus/blur events for better state management
        window.addEventListener('focus', function() {
            console.log('🔍 Window focused');
        });

        window.addEventListener('blur', function() {
            console.log('🔍 Window blurred - saving state...');
            stateManager.saveState();
        });

        // Cleanup on page unload
        window.addEventListener('unload', function() {
            stateManager.stopHeartbeat();
        });

        // Audio format support detection
        function detectAudioSupport() {
            const audio = document.createElement('audio');
            const formats = {
                mp3: audio.canPlayType('audio/mpeg'),
                wav: audio.canPlayType('audio/wav'),
                ogg: audio.canPlayType('audio/ogg'),
                m4a: audio.canPlayType('audio/mp4'),
                aac: audio.canPlayType('audio/aac')
            };
            
            console.log('🎧 Browser audio format support:', formats);
            return formats;
        }
        
        // Enhanced audio source with format fallbacks
        function getOptimalAudioSource(filePath) {
            const supportedFormats = detectAudioSupport();
            const baseUrl = filePath.replace(/\.[^/.]+$/, ''); // Remove extension
            
            // Priority order: MP3 (best compatibility) -> M4A -> WAV -> OGG
            const formatPriority = ['mp3', 'm4a', 'wav', 'ogg'];
            
            for (let format of formatPriority) {
                if (supportedFormats[format] !== '') {
                    return baseUrl + '.' + format;
                }
            }
            
            // Fallback to original
            return filePath;
        }
        
        // Audio preloader for smoother playback
        function preloadAudio(trackData) {
            return new Promise((resolve, reject) => {
                const preloadElement = new Audio();
                preloadElement.preload = 'metadata';
                
                preloadElement.addEventListener('loadedmetadata', () => {
                    console.log(`✅ Preloaded: ${trackData.title} - Duration: ${Math.round(preloadElement.duration)}s`);
                    resolve(preloadElement);
                });
                
                preloadElement.addEventListener('error', (error) => {
                    console.warn(`⚠️ Preload failed for: ${trackData.title}`, error);
                    reject(error);
                });
                
                // Set source with format optimization
                let audioSrc;
                if (trackData.file_path.startsWith('http')) {
                    audioSrc = trackData.file_path;
                } else {
                    const cleanPath = trackData.file_path.replace(/^(uploads\/music\/|music\/|\/)/g, '');
                    audioSrc = `<?php echo UPLOAD_URL; ?>music/${cleanPath}`;
                }
                
                preloadElement.src = getOptimalAudioSource(audioSrc);
            });
        }
    </script>
</body>
</html>
