import Ajax from 'core/ajax';
import Notification from 'core/notification';

const formatTime = (seconds) => {
    const value = Math.max(0, Math.floor(Number(seconds) || 0));
    const hours = Math.floor(value / 3600);
    const minutes = Math.floor((value % 3600) / 60);
    const secs = value % 60;
    return hours > 0
        ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
        : `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
};

export const init = (config) => {
    const player = document.getElementById('videorubric-player');
    const saveState = document.getElementById('videorubric-save-state');
    if (!player) {
        return;
    }

    const setState = (text) => { if (saveState) { saveState.textContent = text || ''; } };
    const current = document.getElementById('videorubric-current-time');
    const duration = document.getElementById('videorubric-duration');
    const commentTime = document.getElementById('videorubric-comment-time');

    const syncTime = () => {
        const formatted = formatTime(player.currentTime);
        if (current) { current.textContent = formatted; }
        if (commentTime) { commentTime.textContent = formatted; }
    };
    player.addEventListener('timeupdate', syncTime);
    player.addEventListener('loadedmetadata', () => {
        if (duration) { duration.textContent = formatTime(player.duration); }
        syncTime();
    });

    document.getElementById('videorubric-speed')?.addEventListener('change', (event) => {
        player.playbackRate = Number(event.target.value) || 1;
    });

    document.querySelectorAll('.videorubric-level-choice').forEach((button) => {
        button.addEventListener('click', async() => {
            const criterion = button.closest('.videorubric-grade-criterion');
            if (!criterion) { return; }
            setState(config.strings.saving);
            try {
                const [result] = await Promise.all(Ajax.call([{
                    methodname: 'mod_videorubric_save_grade_level',
                    args: {
                        submissionid: config.submissionid,
                        criterionid: Number(criterion.dataset.criterionid),
                        levelid: Number(button.dataset.levelid),
                    },
                }]));
                criterion.querySelectorAll('.videorubric-level-choice').forEach((item) => item.classList.remove('is-selected'));
                button.classList.add('is-selected');
                const score = document.getElementById('videorubric-final-score');
                if (score) { score.textContent = Number(result.finalscore).toFixed(2); }
                setState(config.strings.saved);
            } catch (error) {
                setState('');
                Notification.exception(error);
            }
        });
    });

    let feedbackTimer = null;
    const feedback = document.getElementById('videorubric-feedback-text');
    feedback?.addEventListener('input', () => {
        window.clearTimeout(feedbackTimer);
        setState(config.strings.saving);
        feedbackTimer = window.setTimeout(async() => {
            try {
                await Promise.all(Ajax.call([{
                    methodname: 'mod_videorubric_save_feedback_text',
                    args: {submissionid: config.submissionid, feedback: feedback.value},
                }]));
                setState(config.strings.saved);
            } catch (error) {
                setState('');
                Notification.exception(error);
            }
        }, 650);
    });

    const comments = document.getElementById('videorubric-comments');
    const bindComment = (row) => {
        row.querySelector('.videorubric-seek-comment')?.addEventListener('click', () => {
            player.currentTime = Number(row.dataset.time) || 0;
            player.focus();
        });
        row.querySelector('.videorubric-delete-comment')?.addEventListener('click', async() => {
            try {
                await Promise.all(Ajax.call([{
                    methodname: 'mod_videorubric_delete_comment',
                    args: {commentid: Number(row.dataset.commentid)},
                }]));
                row.remove();
            } catch (error) {
                Notification.exception(error);
            }
        });
    };
    comments?.querySelectorAll('.videorubric-comment').forEach(bindComment);

    document.getElementById('videorubric-add-comment')?.addEventListener('click', async() => {
        const input = document.getElementById('videorubric-comment-input');
        const text = input?.value.trim();
        if (!text) { return; }
        try {
            const [result] = await Promise.all(Ajax.call([{
                methodname: 'mod_videorubric_add_comment',
                args: {
                    submissionid: config.submissionid,
                    timeposition: Math.floor(player.currentTime),
                    comment: text,
                },
            }]));
            const row = document.createElement('div');
            row.className = 'videorubric-comment';
            row.dataset.commentid = result.id;
            row.dataset.time = result.timeposition;
            const seek = document.createElement('button');
            seek.type = 'button';
            seek.className = 'btn btn-link videorubric-seek-comment';
            seek.textContent = result.formattedtime;
            const body = document.createElement('span');
            body.className = 'flex-grow-1';
            body.textContent = result.comment;
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn-sm btn-outline-danger videorubric-delete-comment';
            del.textContent = '×';
            row.append(seek, body, del);
            comments?.append(row);
            bindComment(row);
            if (input) { input.value = ''; }
        } catch (error) {
            Notification.exception(error);
        }
    });

    let audioRecorder = null;
    let audioStream = null;
    let audioBlob = null;
    let audioStartedAt = 0;
    const recordButton = document.getElementById('videorubric-audio-record');
    const stopButton = document.getElementById('videorubric-audio-stop');
    const saveAudioButton = document.getElementById('videorubric-audio-save');
    const audioPreview = document.getElementById('videorubric-audio-preview');
    const audioStatus = document.getElementById('videorubric-audio-status');

    recordButton?.addEventListener('click', async() => {
        try {
            if (audioStream) { audioStream.getTracks().forEach((track) => track.stop()); }
            audioStream = await navigator.mediaDevices.getUserMedia({audio: true});
            const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : '';
            const chunks = [];
            audioRecorder = mimeType ? new MediaRecorder(audioStream, {mimeType}) : new MediaRecorder(audioStream);
            audioRecorder.addEventListener('dataavailable', (event) => { if (event.data.size) { chunks.push(event.data); } });
            audioRecorder.addEventListener('stop', () => {
                audioBlob = new Blob(chunks, {type: audioRecorder.mimeType || 'audio/webm'});
                if (audioPreview) {
                    audioPreview.src = URL.createObjectURL(audioBlob);
                    audioPreview.classList.remove('d-none');
                }
                saveAudioButton?.classList.remove('d-none');
                recordButton.classList.remove('d-none');
                stopButton?.classList.add('d-none');
                if (audioStatus) { audioStatus.textContent = ''; }
                audioStream?.getTracks().forEach((track) => track.stop());
            });
            audioStartedAt = Date.now();
            audioRecorder.start();
            recordButton.classList.add('d-none');
            stopButton?.classList.remove('d-none');
            saveAudioButton?.classList.add('d-none');
            if (audioStatus) { audioStatus.textContent = config.strings.recording; }
        } catch (error) {
            Notification.alert('Microphone', error.message || String(error));
        }
    });

    stopButton?.addEventListener('click', () => {
        if (audioRecorder && audioRecorder.state !== 'inactive') { audioRecorder.stop(); }
    });

    saveAudioButton?.addEventListener('click', async() => {
        if (!audioBlob) { return; }
        const form = new FormData();
        form.append('cmid', config.cmid);
        form.append('submissionid', config.submissionid);
        form.append('sesskey', config.sesskey);
        form.append('duration', Math.floor((Date.now() - audioStartedAt) / 1000));
        form.append('audio', audioBlob, 'feedback.webm');
        if (audioStatus) { audioStatus.textContent = config.strings.saving; }
        try {
            const response = await fetch(config.audioUploadUrl, {method: 'POST', body: form, credentials: 'same-origin'});
            const data = await response.json();
            if (!response.ok || !data.success) { throw new Error(data.message || 'Upload failed'); }
            if (audioPreview) { audioPreview.src = data.url; }
            const existing = document.getElementById('videorubric-existing-audio');
            if (existing) { existing.src = data.url; }
            if (audioStatus) { audioStatus.textContent = config.strings.saved; }
            saveAudioButton.classList.add('d-none');
        } catch (error) {
            Notification.alert('Audio', error.message || String(error));
        }
    });

    document.getElementById('videorubric-finalize-grade')?.addEventListener('click', async(event) => {
        event.target.disabled = true;
        setState(config.strings.saving);
        try {
            const [result] = await Promise.all(Ajax.call([{
                methodname: 'mod_videorubric_finalize_grade',
                args: {submissionid: config.submissionid},
            }]));
            const score = document.getElementById('videorubric-final-score');
            if (score) { score.textContent = Number(result.finalscore).toFixed(2); }
            setState(config.strings.saved);
        } catch (error) {
            event.target.disabled = false;
            setState('');
            Notification.exception(error);
        }
    });
};
