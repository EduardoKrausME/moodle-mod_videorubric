import Notification from 'core/notification';

export const init = (config) => {
    const camera = document.getElementById('videorubric-camera');
    const preview = document.getElementById('videorubric-recorded-preview');
    const enable = document.getElementById('videorubric-enable-camera');
    const start = document.getElementById('videorubric-start-record');
    const stop = document.getElementById('videorubric-stop-record');
    const upload = document.getElementById('videorubric-upload-recording');
    const status = document.getElementById('videorubric-recording-status');
    if (!camera || !enable || !start || !stop || !upload) { return; }

    let stream = null;
    let recorder = null;
    let blob = null;
    let started = 0;
    let timer = null;

    const setStatus = (text) => { if (status) { status.textContent = text || ''; } };
    const stopStream = () => { stream?.getTracks().forEach((track) => track.stop()); };

    enable.addEventListener('click', async() => {
        try {
            stopStream();
            stream = await navigator.mediaDevices.getUserMedia({video: true, audio: true});
            camera.srcObject = stream;
            start.disabled = false;
            setStatus('');
        } catch (error) {
            Notification.alert('Camera', error.message || String(error));
        }
    });

    start.addEventListener('click', () => {
        if (!stream) { return; }
        const preferred = [
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm',
            'video/mp4',
        ].find((type) => MediaRecorder.isTypeSupported(type));
        const chunks = [];
        recorder = preferred ? new MediaRecorder(stream, {mimeType: preferred}) : new MediaRecorder(stream);
        recorder.addEventListener('dataavailable', (event) => { if (event.data.size) { chunks.push(event.data); } });
        recorder.addEventListener('stop', () => {
            window.clearInterval(timer);
            blob = new Blob(chunks, {type: recorder.mimeType || 'video/webm'});
            preview.src = URL.createObjectURL(blob);
            preview.classList.remove('d-none');
            upload.classList.remove('d-none');
            start.classList.remove('d-none');
            stop.classList.add('d-none');
            setStatus('');
        });
        recorder.start(1000);
        started = Date.now();
        start.classList.add('d-none');
        stop.classList.remove('d-none');
        upload.classList.add('d-none');
        timer = window.setInterval(() => {
            const seconds = Math.floor((Date.now() - started) / 1000);
            setStatus(`${seconds}s`);
            if (config.maxduration > 0 && seconds >= config.maxduration && recorder.state !== 'inactive') {
                recorder.stop();
            }
        }, 500);
    });

    stop.addEventListener('click', () => {
        if (recorder && recorder.state !== 'inactive') { recorder.stop(); }
    });

    upload.addEventListener('click', async() => {
        if (!blob) { return; }
        const form = new FormData();
        form.append('cmid', config.cmid);
        form.append('submissionid', config.submissionid);
        form.append('sesskey', config.sesskey);
        form.append('duration', Math.floor((Date.now() - started) / 1000));
        form.append('video', blob, blob.type.includes('mp4') ? 'recording.mp4' : 'recording.webm');
        setStatus('Saving…');
        upload.disabled = true;
        try {
            const response = await fetch(config.uploadUrl, {method: 'POST', body: form, credentials: 'same-origin'});
            const data = await response.json();
            if (!response.ok || !data.success) { throw new Error(data.message || 'Upload failed'); }
            setStatus('Saved');
            window.location.reload();
        } catch (error) {
            upload.disabled = false;
            Notification.alert('Video', error.message || String(error));
        }
    });
};
