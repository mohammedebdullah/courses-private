/**
 * Security JavaScript
 * Anti-download, anti-recording, and anti-inspect measures
 */

(function() {
    'use strict';
    
    // Disable right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Disable text selection
    document.addEventListener('selectstart', function(e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });
    
    // Disable drag
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Disable copy
    document.addEventListener('copy', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Disable keyboard shortcuts for DevTools and source viewing
    document.addEventListener('keydown', function(e) {
        // F12 - DevTools
        if (e.key === 'F12') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+I - DevTools
        if (e.ctrlKey && e.shiftKey && e.key === 'I') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+J - Console
        if (e.ctrlKey && e.shiftKey && e.key === 'J') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+C - Inspect Element
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+U - View Source
        if (e.ctrlKey && e.key === 'u') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+S - Save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+P - Print (can be used for PDF)
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            return false;
        }
    });
    
    // Detect DevTools opening
    let devToolsOpen = false;
    const threshold = 160;
    
    function detectDevTools() {
        const widthThreshold = window.outerWidth - window.innerWidth > threshold;
        const heightThreshold = window.outerHeight - window.innerHeight > threshold;
        
        if (widthThreshold || heightThreshold) {
            if (!devToolsOpen) {
                devToolsOpen = true;
                onDevToolsOpen();
            }
        } else {
            devToolsOpen = false;
        }
    }
    
    function onDevToolsOpen() {
        // Pause all audio when DevTools is detected
        const audios = document.querySelectorAll('audio');
        audios.forEach(audio => audio.pause());
        
        // Optional: Show warning or redirect
        console.clear();
        console.log('%cStop!', 'color: red; font-size: 50px; font-weight: bold;');
        console.log('%cThis is a browser feature intended for developers.', 'font-size: 18px;');
    }
    
    // Check periodically - increased interval to reduce CPU usage
    setInterval(detectDevTools, 5000); // Changed from 3000ms to 5000ms for better performance
    
    // Disable print
    window.addEventListener('beforeprint', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Attempt to detect screen recording (limited effectiveness)
    async function detectScreenCapture() {
        try {
            // Check if display capture is active
            if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
                // We can't actually detect if recording is happening,
                // but we can refuse to play if user tries to use getDisplayMedia
            }
        } catch (e) {
            // Ignore errors
        }
    }
    
    // Detect if page visibility changes (tab switching during recording)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Pause audio when tab is not visible
            const audios = document.querySelectorAll('audio');
            audios.forEach(audio => {
                if (!audio.paused) {
                    audio.dataset.wasPlaying = 'true';
                    audio.pause();
                }
            });
        }
    });
    
    // Prevent media download via blob URLs
    const originalCreateObjectURL = URL.createObjectURL;
    URL.createObjectURL = function(blob) {
        // Block audio/video blob creation from external scripts
        if (blob && (blob.type.startsWith('audio/') || blob.type.startsWith('video/'))) {
            console.warn('Blob URL creation blocked for media');
            return '';
        }
        return originalCreateObjectURL.apply(this, arguments);
    };
    
    // Override Web Audio API to prevent recording
    if (window.AudioContext || window.webkitAudioContext) {
        const OriginalAudioContext = window.AudioContext || window.webkitAudioContext;
        
        window.AudioContext = window.webkitAudioContext = function() {
            const context = new OriginalAudioContext();
            
            // Block createMediaStreamDestination (used for recording)
            const originalCreateMediaStreamDestination = context.createMediaStreamDestination;
            context.createMediaStreamDestination = function() {
                console.warn('Media stream destination creation blocked');
                throw new Error('Operation not permitted');
            };
            
            return context;
        };
    }
    
    // Block MediaRecorder
    if (window.MediaRecorder) {
        window.MediaRecorder = function() {
            throw new Error('Recording is not permitted');
        };
    }
    
    // Disable picture-in-picture for audio elements
    document.addEventListener('DOMContentLoaded', function() {
        const audios = document.querySelectorAll('audio');
        audios.forEach(audio => {
            audio.disablePictureInPicture = true;
            audio.controlsList = 'nodownload';
            audio.addEventListener('ratechange', function() {
                if (audio.playbackRate > 1.5) {
                    audio.playbackRate = 1.5;
                }
            });
        });
    });
    
    // Monitor for download attempts
    document.addEventListener('click', function(e) {
        const target = e.target;
        if (target.tagName === 'A' && target.hasAttribute('download')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Detect debugger presence  
    (function detectDebugger() {
        const start = performance.now();
        debugger;
        const end = performance.now();
        
        if (end - start > 100) {
            // Debugger was probably open
            onDevToolsOpen();
        }
    })();
    
    // Clear console periodically - increased interval to reduce impact
    setInterval(function() {
        console.clear();
    }, 15000); // Changed from 10000ms to 15000ms for minimal performance impact
    
})();
