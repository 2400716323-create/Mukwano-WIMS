@extends('catalog.layout')

@section('title', 'Scan QR code')

@section('content')
    <style>
        .scanner-workstation {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: 16px;
            align-items: start;
        }

        @media (max-width: 960px) {
            .scanner-workstation {
                grid-template-columns: 1fr;
            }
        }

        .viewport-container {
            position: relative;
            width: 100%;
            height: 320px;
            background: #090d16;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #1e293b;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.4);
        }

        #camera-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .viewfinder-hud {
            position: absolute;
            width: 200px;
            height: 200px;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .viewfinder-reticle {
            position: relative;
            width: 100%;
            height: 100%;
            border: 1px dashed rgba(255, 255, 255, 0.25);
            border-radius: 14px;
        }

        .hud-corner {
            position: absolute;
            width: 18px;
            height: 18px;
            border-color: #3b82f6;
            border-style: solid;
        }

        .hud-tl { top: -2px; left: -2px; border-width: 3px 0 0 3px; border-top-left-radius: 10px; }
        .hud-tr { top: -2px; right: -2px; border-width: 3px 3px 0 0; border-top-right-radius: 10px; }
        .hud-bl { bottom: -2px; left: -2px; border-width: 0 0 3px 3px; border-bottom-left-radius: 10px; }
        .hud-br { bottom: -2px; right: -2px; border-width: 0 3px 3px 0; border-bottom-right-radius: 10px; }

        .scan-laser {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #38bdf8 30%, #60a5fa 70%, transparent);
            box-shadow: 0 0 10px #38bdf8, 0 0 4px #60a5fa;
            animation: laserSweep 2s ease-in-out infinite alternate;
        }

        @keyframes laserSweep {
            0% { top: 6%; opacity: 0.6; }
            50% { opacity: 1; }
            100% { top: 94%; opacity: 0.6; }
        }

        .scanner-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            text-align: center;
            padding: 24px;
            gap: 12px;
        }

        .scanner-status-strip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12.5px;
            color: #475569;
            margin-top: 14px;
        }

        .resolved-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-top: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            animation: fadeIn 0.25s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .resolved-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .guide-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
    </style>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Scan QR Code</h1>
            <p>Scan physical warehouse bin tags or product labels using your camera or handheld 2D barcode scanner.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('locations.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Locations</span>
            </a>
            <a class="button secondary" href="{{ route('products.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Catalog</span>
            </a>
            <a class="button secondary" href="{{ route('inventory.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                <span>Stock Overview</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI / Capability Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Optical Scanner</span>
                <span class="stat-num">Fast</span>
                <span class="stat-sub">Live camera viewfinder</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Location Bins</span>
                <span class="stat-num">Direct</span>
                <span class="stat-sub">Retrieves bin stock</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Product Labels</span>
                <span class="stat-num">Verified</span>
                <span class="stat-sub">SKU & thresholds</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Cryptographic</span>
                <span class="stat-num">Secure</span>
                <span class="stat-sub">Tamper detection</span>
            </div>
        </div>
    </div>

    <!-- Scanner Workstation -->
    <div class="scanner-workstation">
        <!-- Left: Live Optical Camera Viewport -->
        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: #94a3b8;" id="camera-led"></div>
                    <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Camera Viewfinder</h2>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button id="start-camera" type="button" class="button" style="padding: 5px 12px; font-size: 12px; background: #2563eb;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <span>Start Camera</span>
                    </button>
                    <button id="stop-camera" class="button secondary" type="button" disabled style="padding: 5px 12px; font-size: 12px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>
                        <span>Stop</span>
                    </button>
                </div>
            </div>

            <!-- Video Viewport Frame -->
            <div class="viewport-container">
                <video id="camera-preview" playsinline muted style="display:none;"></video>
                
                <div id="viewfinder-hud" class="viewfinder-hud" style="display:none;">
                    <div class="viewfinder-reticle">
                        <span class="hud-corner hud-tl"></span>
                        <span class="hud-corner hud-tr"></span>
                        <span class="hud-corner hud-bl"></span>
                        <span class="hud-corner hud-br"></span>
                        <div class="scan-laser"></div>
                    </div>
                </div>

                <div id="camera-placeholder" class="scanner-placeholder">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="1.5">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: #cbd5e1; font-size: 13.5px;">Camera Feed Inactive</p>
                        <p style="margin: 4px 0 0; color: #64748b; font-size: 12px;">Click "Start Camera" or type/paste code on the right</p>
                    </div>
                </div>
            </div>

            <!-- Camera Feedback Strip -->
            <div class="scanner-status-strip">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span id="camera-message" role="status" aria-live="polite">Ready. Position code in front of camera or enter manually.</span>
            </div>
        </div>

        <!-- Right: Manual Entry & Quick Guide -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <!-- Manual / Handheld Scanner Input Card -->
            <div class="card" style="padding: 20px;">
                <h2 style="margin: 0 0 6px; font-size: 15px; color: #0f172a;">Manual & Scanner Input</h2>
                <p style="margin: 0 0 16px; font-size: 12.5px; color: #64748b;">
                    Type, paste, or scan with a USB/Bluetooth barcode gun.
                </p>

                <form id="scan-form" method="POST" action="{{ route('qr.resolve') }}">
                    @csrf
                    <div class="field" style="margin-bottom: 14px;">
                        <label for="code" style="font-size: 12px; font-weight: 600; color: #334155;">QR Code String / Barcode</label>
                        <div style="position: relative;">
                            <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                            <input id="code" name="code" type="text" maxlength="80" required autocomplete="off" placeholder="e.g. W1|L|1|hash or W1|P|1|hash" style="width: 100%; padding-left: 36px; font-family: monospace; font-size: 13px;">
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="submit" class="button" style="flex: 1;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Validate & View</span>
                        </button>
                        <button type="button" class="button secondary" onclick="document.getElementById('code').value=''; document.getElementById('code').focus();" style="padding: 8px 12px;" title="Clear input">
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Operator Scanning Tips Card -->
            <div class="card" style="padding: 18px;">
                <h3 style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">Scanning Recommendations</h3>
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: #475569;">
                    <div class="guide-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                        <span>Hold the camera steady 15–20 cm from the label.</span>
                    </div>
                    <div class="guide-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <span>Ensure adequate lighting without sharp glare.</span>
                    </div>
                    <div class="guide-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" style="flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span>WIMS labels contain signed HMAC payloads to prevent counterfeit tampering.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resolved Results Container -->
    <div id="scan-result" style="margin-top: 16px;" aria-live="polite"></div>
@endsection

@push('scripts')
    <script>
        const startButton = document.getElementById('start-camera');
        const stopButton = document.getElementById('stop-camera');
        const cameraMessage = document.getElementById('camera-message');
        const cameraLed = document.getElementById('camera-led');
        const cameraPlaceholder = document.getElementById('camera-placeholder');
        const viewfinderHud = document.getElementById('viewfinder-hud');
        const video = document.getElementById('camera-preview');
        const codeInput = document.getElementById('code');
        const form = document.getElementById('scan-form');
        const result = document.getElementById('scan-result');
        let cameraStream = null;
        let detector = null;
        let scanning = false;
        let requestPending = false;

        function setStatus(text, type = 'info') {
            cameraMessage.textContent = text;
            if (type === 'error') {
                cameraMessage.style.color = '#dc2626';
            } else if (type === 'success') {
                cameraMessage.style.color = '#16a34a';
            } else {
                cameraMessage.style.color = '#475569';
            }
        }

        function stopCamera() {
            scanning = false;
            if (cameraStream) {
                cameraStream.getTracks().forEach((track) => track.stop());
                cameraStream = null;
            }
            video.srcObject = null;
            video.style.display = 'none';
            viewfinderHud.style.display = 'none';
            cameraPlaceholder.style.display = 'flex';
            cameraLed.style.background = '#94a3b8';
            startButton.disabled = false;
            stopButton.disabled = true;
        }

        async function scanFrame() {
            if (!scanning || requestPending || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
                if (scanning) requestAnimationFrame(scanFrame);
                return;
            }

            try {
                const codes = await detector.detect(video);
                if (codes.length > 0) {
                    codeInput.value = codes[0].rawValue;
                    stopCamera();
                    setStatus('QR code captured! Resolving details...', 'success');
                    form.requestSubmit();
                    return;
                }
            } catch (error) {
                setStatus('Camera scanning interrupted. Please enter code manually.', 'error');
                stopCamera();
                return;
            }

            if (scanning) requestAnimationFrame(scanFrame);
        }

        startButton.addEventListener('click', async () => {
            if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                setStatus('Native camera QR scanning is not supported by this browser. Please enter the code manually.', 'error');
                return;
            }

            try {
                detector = new BarcodeDetector({ formats: ['qr_code'] });
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });
                video.srcObject = cameraStream;
                video.style.display = 'block';
                viewfinderHud.style.display = 'flex';
                cameraPlaceholder.style.display = 'none';
                cameraLed.style.background = '#10b981';
                await video.play();
                scanning = true;
                startButton.disabled = true;
                stopButton.disabled = false;
                setStatus('Camera active. Point at a location or product label.', 'success');
                requestAnimationFrame(scanFrame);
            } catch (error) {
                stopCamera();
                setStatus('Camera access was denied or device is unavailable. Enter code manually.', 'error');
            }
        });

        stopButton.addEventListener('click', () => {
            stopCamera();
            setStatus('Camera stopped.');
        });

        window.addEventListener('pagehide', stopCamera);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (requestPending) return;

            requestPending = true;
            result.replaceChildren();
            setStatus('Validating cryptographic QR signature...', 'info');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (!response.ok) {
                    const message = Object.values(data.errors || {}).flat().join(' ');
                    throw new Error(message || 'The QR code could not be validated.');
                }

                // Render Beautiful Result Card
                const card = document.createElement('div');
                card.className = 'resolved-card';

                const isLocation = data.type === 'location';
                const typeColor = isLocation ? '#10b981' : '#2563eb';
                const typeBg = isLocation ? 'rgba(16, 185, 129, 0.12)' : 'rgba(37, 99, 235, 0.12)';
                const iconSvg = isLocation
                    ? `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="${typeColor}" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>`
                    : `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="${typeColor}" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>`;

                let contentsHtml = '';
                if (data.contents && data.contents.length > 0) {
                    contentsHtml = `
                        <div style="margin-top: 16px;">
                            <h3 style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">Products & Batches in this Bin (${data.contents.length})</h3>
                            <div class="modern-table-card" style="margin-bottom: 0;">
                                <table class="clean-table">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>SKU</th>
                                            <th>Batch Number</th>
                                            <th style="text-align: right;">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.contents.map(item => `
                                            <tr>
                                                <td style="font-weight: 600; color: #0f172a;">${item.product}</td>
                                                <td><span class="pill-code">${item.sku}</span></td>
                                                <td style="font-family: monospace; color: #2563eb; font-weight: 600;">${item.batch_number}</td>
                                                <td style="text-align: right; font-weight: 700;">${item.quantity}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } else if (isLocation) {
                    contentsHtml = `
                        <div style="margin-top: 14px; padding: 12px 14px; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1; font-size: 12.5px; color: #64748b;">
                            No positive stock balances currently logged in this bin location.
                        </div>
                    `;
                }

                card.innerHTML = `
                    <div class="resolved-header">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: ${typeBg}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                ${iconSvg}
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 3px;">
                                    <span class="pill-code" style="text-transform: uppercase;">${data.type}</span>
                                    <span class="badge-${data.status === 'active' ? 'success' : 'muted'}">${data.status.toUpperCase()}</span>
                                </div>
                                <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">${data.name}</h2>
                                <p style="margin: 3px 0 0; font-size: 12.5px; color: #64748b;">
                                    ${isLocation ? (data.path || 'Warehouse Location') : ('SKU: ' + data.sku + ' · Category: ' + data.category)}
                                </p>
                            </div>
                        </div>
                        <div>
                            <a href="${data.url}" class="button" style="padding: 7px 14px; font-size: 12px; background: #2563eb;">
                                <span>Open Full Record</span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                    ${contentsHtml}
                `;

                result.appendChild(card);
                setStatus('QR Code successfully validated and loaded!', 'success');
            } catch (error) {
                const errCard = document.createElement('div');
                errCard.className = 'card';
                errCard.style.padding = '16px';
                errCard.style.borderLeft = '4px solid #ef4444';
                errCard.style.background = '#fef2f2';
                errCard.style.marginTop = '16px';
                errCard.innerHTML = `
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <strong style="color: #991b1b; font-size: 13.5px; display: block;">Validation Failed</strong>
                            <p style="margin: 3px 0 0; color: #b91c1c; font-size: 12.5px;">${error.message || 'The QR code could not be verified or has been tampered with.'}</p>
                        </div>
                    </div>
                `;
                result.appendChild(errCard);
                setStatus(error.message || 'Validation failed. Check code string and try again.', 'error');
            } finally {
                requestPending = false;
            }
        });
    </script>
@endpush
