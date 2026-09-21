// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * app.js
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/str', 'core/notification'], function (Ajax, Str, Notification) {
    const HEARTBEAT = 5;

    class Adapter {
        constructor(root) {
            this.root = root;
            this.current = 0;
            this.duration = 0;
            this.rate = 1;
            this.events = {};
        }

        on(name, callback) {
            if (!this.events[name]) {
                this.events[name] = [];
            }
            this.events[name].push(callback);
        }

        emit(name, ...args) {
            (this.events[name] || []).forEach((callback) => callback(...args));
        }

        getCurrentTime() {
            return Number(this.current || 0);
        }

        getDuration() {
            return Number(this.duration || 0);
        }

        getPlaybackRate() {
            return Number(this.rate || 1);
        }

        setPlaybackRate(rate) {
            this.rate = Number(rate || 1);
        }
    }

    class NativeAdapter extends Adapter {
        constructor(video) {
            super(video);
            this.video = video;
            ['play', 'pause', 'ended'].forEach((event) => video.addEventListener(event, () => this.emit(event)));
            video.addEventListener('loadedmetadata', () => {
                this.duration = video.duration || 0;
            });
            video.addEventListener('timeupdate', () => {
                this.current = video.currentTime || 0;
                this.duration = video.duration || 0;
                this.rate = video.playbackRate || 1;
                this.emit('timeupdate', this.current);
            });
            video.addEventListener('seeking', () => this.emit('seek', video.currentTime || 0));
            video.addEventListener('ratechange', () => {
                this.rate = video.playbackRate || 1;
            });
        }

        seek(time) {
            this.video.currentTime = Math.max(0, Number(time || 0));
        }

        setPlaybackRate(rate) {
            this.video.playbackRate = Math.max(0.25, Number(rate || 1));
            this.rate = this.video.playbackRate;
        }
    }

    const loadYoutube = () => {
        if (window.YT && window.YT.Player) {
            return Promise.resolve();
        }
        if (window.videoerrorhuntYTPromise) {
            return window.videoerrorhuntYTPromise;
        }
        window.videoerrorhuntYTPromise = new Promise((resolve) => {
            const previous = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {
                if (typeof previous === 'function') {
                    previous();
                }
                resolve();
            };
            const script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(script);
        });
        return window.videoerrorhuntYTPromise;
    };

    class YouTubeAdapter extends Adapter {
        static create(element) {
            return loadYoutube().then(() => new Promise((resolve) => {
                const adapter = new YouTubeAdapter(element);
                adapter.player = new window.YT.Player(element, {
                    videoId: element.dataset.videoid,
                    playerVars: {rel: 0, playsinline: 1},
                    events: {
                        onReady: (event) => {
                            adapter.duration = event.target.getDuration() || 0;
                            resolve(adapter);
                        },
                        onStateChange: (event) => {
                            if (event.data === window.YT.PlayerState.PLAYING) {
                                adapter.emit('play');
                            }
                            if (event.data === window.YT.PlayerState.PAUSED) {
                                adapter.emit('pause');
                            }
                            if (event.data === window.YT.PlayerState.ENDED) {
                                adapter.emit('ended');
                            }
                        },
                        onPlaybackRateChange: (event) => {
                            adapter.rate = Number(event.data || 1);
                        }
                    }
                });
                adapter.timer = window.setInterval(() => {
                    if (!adapter.player || typeof adapter.player.getCurrentTime !== 'function') {
                        return;
                    }
                    const previous = adapter.current;
                    adapter.current = adapter.player.getCurrentTime() || 0;
                    adapter.duration = adapter.player.getDuration() || adapter.duration;
                    adapter.rate = adapter.player.getPlaybackRate() || 1;
                    if (Math.abs(adapter.current - previous) > 2.5) {
                        adapter.emit('seek', adapter.current, previous);
                    }
                    adapter.emit('timeupdate', adapter.current);
                }, 500);
            }));
        }

        seek(time) {
            this.player.seekTo(Math.max(0, Number(time || 0)), true);
        }

        setPlaybackRate(rate) {
            if (this.player && typeof this.player.setPlaybackRate === 'function') {
                this.player.setPlaybackRate(Number(rate || 1));
            }
        }
    }

    const loadVimeo = () => {
        if (window.Vimeo && window.Vimeo.Player) {
            return Promise.resolve();
        }
        if (window.videoerrorhuntVimeoPromise) {
            return window.videoerrorhuntVimeoPromise;
        }
        window.videoerrorhuntVimeoPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://player.vimeo.com/api/player.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
        return window.videoerrorhuntVimeoPromise;
    };

    class VimeoAdapter extends Adapter {
        static create(iframe) {
            return loadVimeo().then(() => {
                const adapter = new VimeoAdapter(iframe);
                adapter.player = new window.Vimeo.Player(iframe);
                adapter.player.on('play', () => adapter.emit('play'));
                adapter.player.on('pause', () => adapter.emit('pause'));
                adapter.player.on('ended', () => adapter.emit('ended'));
                adapter.player.on('timeupdate', (data) => {
                    adapter.current = Number(data.seconds || 0);
                    adapter.duration = Number(data.duration || 0);
                    adapter.emit('timeupdate', adapter.current);
                });
                adapter.player.on('seeked', (data) => {
                    const previous = adapter.current;
                    adapter.current = Number(data.seconds || 0);
                    adapter.emit('seek', adapter.current, previous);
                });
                adapter.player.on('playbackratechange', (data) => {
                    adapter.rate = Number(data.playbackRate || 1);
                });
                return adapter.player.getDuration().then((duration) => {
                    adapter.duration = Number(duration || 0);
                    return adapter;
                });
            });
        }

        seek(time) {
            this.player.setCurrentTime(Math.max(0, Number(time || 0)));
        }

        setPlaybackRate(rate) {
            if (this.player) {
                this.player.setPlaybackRate(Number(rate || 1)).catch(() => {
                });
            }
        }
    }

    const playerFor = (root) => {
        const native = root.querySelector('[data-region="native-video"]');
        if (native) {
            return Promise.resolve(new NativeAdapter(native));
        }
        const youtube = root.querySelector('[data-region="youtube"]');
        if (youtube) {
            return YouTubeAdapter.create(youtube);
        }
        const vimeo = root.querySelector('[data-region="vimeo"]');
        if (vimeo) {
            return VimeoAdapter.create(vimeo);
        }
        return Promise.reject(new Error('No supported player source found.'));
    };

    class App {
        constructor(root) {
            this.root = root;
            this.config = JSON.parse(root.dataset.config || '{}');
            this.playing = false;
            this.last = 0;
            this.pendingStart = null;
            this.pendingEnd = null;
            this.sequence = 0;
            this.session = this.randomKey();
            this.sending = false;
            this.queueKey = 'videoerrorhunt:' + this.config.cmid + ':' + (this.config.userid || 0) + ':queue';
            this.memoryQueue = [];
            this.storageAvailable = true;
        }

        init() {
            return playerFor(this.root).then((player) => {
                this.player = player;
                this.bindPlayer();
                this.bindUi();
                this.applyResume();
                this.enforcePlaybackRate();
                this.drain();
                this.interval = window.setInterval(() => {
                    if (this.playing) {
                        this.flush('playing');
                    }
                }, HEARTBEAT * 1000);
                return this;
            }).catch(Notification.exception);
        }

        bindPlayer() {
            this.player.on('play', () => {
                this.playing = true;
                this.last = this.player.getCurrentTime();
                this.pendingStart = this.last;
                this.pendingEnd = this.last;
            });
            this.player.on('timeupdate', (current) => {
                current = Number(current || 0);
                const out = this.root.querySelector('[data-region="current-time"]');
                if (out) {
                    out.textContent = this.formatTime(current);
                }
                this.enforcePlaybackRate();
                if (this.playing) {
                    const delta = current - this.last;
                    const rate = Math.max(0.25, this.player.getPlaybackRate());
                    if (delta >= 0 && delta <= Math.max(3, rate * 3)) {
                        if (this.pendingStart === null) {
                            this.pendingStart = this.last;
                        }
                        this.pendingEnd = current;
                    }
                }
                this.last = current;
            });
            this.player.on('pause', () => {
                this.playing = false;
                this.flush('paused');
            });
            this.player.on('ended', () => {
                this.playing = false;
                this.flush('ended');
            });
            this.player.on('seek', (current, previous) => {
                previous = Number(previous || this.last || 0);
                current = Number(current || 0);
                if (!this.config.allowseek && Math.abs(current - previous) > 0.5 && !this.isWatched(current)) {
                    this.player.seek(previous);
                    this.message('seekblocked', 'warning');
                    return;
                }
                this.flush('seeking');
                this.last = current;
                this.pendingStart = this.playing ? current : null;
                this.pendingEnd = this.pendingStart;
            });
            window.addEventListener('pagehide', () => this.flush('closed'));
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.flush('hidden');
                } else {
                    this.drain();
                }
            });
            window.addEventListener('online', () => this.drain());
        }

        bindUi() {
            const mark = this.root.querySelector('[data-action="mark"]');
            if (mark) {
                mark.addEventListener('click', () => this.addMark());
            }
            const finish = this.root.querySelector('[data-action="finish"]');
            if (finish) {
                finish.addEventListener('click', () => this.finish());
            }
            this.root.querySelectorAll('[data-action="seekseconds"]').forEach((button) => button.addEventListener('click', () => this.player.seek(Number(button.dataset.time || 0))));
            this.root.querySelectorAll('[data-action="seek"]').forEach((button) => button.addEventListener('click', () => this.player.seek(this.parseTime(button.dataset.time || '0'))));
        }

        addMark() {
            const field = this.root.querySelector('[data-region="explanation"]');
            const explanation = field ? field.value.trim() : '';
            if (!explanation) {
                this.message('explanationrequired', 'danger');
                return;
            }
            Ajax.call([{
                methodname: 'mod_videoerrorhunt_add_mark',
                args: {
                    cmid: Number(this.config.cmid),
                    timepoint: this.player.getCurrentTime(),
                    explanation: explanation
                }
            }])[0]
                .then((response) => {
                    if (field) {
                        field.value = '';
                    }
                    if (response.feedbackrevealed) {
                        this.message(response.correct === 1 ? 'markcorrect' : 'markincorrect', response.correct === 1 ? 'success' : 'danger');
                    } else {
                        this.message('markregistered', 'success');
                    }
                    window.setTimeout(() => window.location.reload(), response.submitted ? 300 : 650);
                }).catch(Notification.exception);
        }

        finish() {
            Ajax.call([{methodname: 'mod_videoerrorhunt_finish_hunt', args: {cmid: Number(this.config.cmid)}}])[0]
                .then(() => window.location.reload()).catch(Notification.exception);
        }

        flush(state) {
            if (!this.config.track || !this.player || this.player.getDuration() <= 0) {
                return;
            }
            const current = this.player.getCurrentTime();
            const start = this.pendingStart === null ? current : this.pendingStart;
            const end = this.pendingEnd === null ? start : this.pendingEnd;
            this.pendingStart = this.playing ? current : null;
            this.pendingEnd = this.pendingStart;
            const queue = this.readQueue();
            queue.push({
                cmid: Number(this.config.cmid),
                currentposition: current,
                duration: this.player.getDuration(),
                playbackrate: this.player.getPlaybackRate(),
                segmentstart: start,
                segmentend: Math.max(start, end),
                sequence: ++this.sequence,
                sessionkey: this.session,
                clienttime: Math.floor(Date.now() / 1000),
                playerstate: state
            });
            this.writeQueue(queue);
            this.drain();
        }

        drain() {
            if (!this.config.track) {
                return;
            }
            const queue = this.readQueue();
            if (this.sending || !queue.length || !navigator.onLine) {
                return;
            }
            const payload = queue[0];
            this.sending = true;
            Ajax.call([{methodname: 'mod_videoerrorhunt_update_progress', args: payload}])[0].then((response) => {
                const currentQueue = this.readQueue();
                const index = currentQueue.findIndex((item) => item.sessionkey === payload.sessionkey && Number(item.sequence) === Number(payload.sequence));
                if (index !== -1) {
                    currentQueue.splice(index, 1);
                }
                this.writeQueue(currentQueue);
                this.sending = false;
                try {
                    this.config.segments = JSON.parse(response.segments || '[]');
                } catch (e) {
                    this.config.segments = [];
                }
                const percent = this.root.querySelector('[data-region="percent"]');
                if (percent) {
                    percent.textContent = Math.round(Number(response.percent || 0)) + '%';
                }
                this.drain();
            }).catch(() => {
                this.sending = false;
            });
        }

        enforcePlaybackRate() {
            const max = Number(this.config.maxplaybackrate || 0);
            if (max > 0 && this.player && this.player.getPlaybackRate() > max + 0.01) {
                this.player.setPlaybackRate(max);
            }
        }

        readQueue() {
            if (!this.storageAvailable) {
                return this.memoryQueue.slice();
            }
            try {
                const value = JSON.parse(window.localStorage.getItem(this.queueKey) || '[]');
                return Array.isArray(value) ? value : [];
            } catch (error) {
                this.storageAvailable = false;
                return this.memoryQueue.slice();
            }
        }

        writeQueue(queue) {
            const capped = queue.slice(-200);
            this.memoryQueue = capped.slice();
            try {
                window.localStorage.setItem(this.queueKey, JSON.stringify(capped));
                this.storageAvailable = true;
                this.memoryQueue = [];
            } catch (error) {
                this.storageAvailable = false;
            }
        }

        applyResume() {
            const position = Number(this.config.lastposition || 0);
            if (position <= 1 || Number(this.config.resumeplayback) === 0) {
                return;
            }
            if (Number(this.config.resumeplayback) === 1) {
                this.player.seek(position);
                return;
            }
            Promise.all([Str.get_string('resumequestion', 'videoerrorhunt', this.formatTime(position)), Str.get_string('resumeyes', 'videoerrorhunt'), Str.get_string('resumeno', 'videoerrorhunt')])
                .then((s) => Notification.confirm('', s[0], s[1], s[2], () => this.player.seek(position), () => this.player.seek(0)));
        }

        isWatched(position) {
            return (this.config.segments || []).some((s) => position >= Number(s[0]) - .5 && position <= Number(s[1]) + .5);
        }

        message(key, type) {
            Str.get_string(key, 'videoerrorhunt').then((msg) => {
                const el = this.root.querySelector('[data-region="message"]');
                if (!el) {
                    return;
                }
                el.textContent = msg;
                el.className = 'alert alert-' + type + ' mt-3';
            });
        }

        randomKey() {
            const bytes = new Uint8Array(24);
            window.crypto.getRandomValues(bytes);
            return Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
        }

        formatTime(seconds) {
            const v = Math.max(0, Math.round(seconds));
            const h = Math.floor(v / 3600), m = Math.floor((v % 3600) / 60), s = v % 60;
            return (h ? String(h).padStart(2, '0') + ':' : '') + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        parseTime(value) {
            const parts = String(value).split(':').map(Number);
            return parts.reduce((total, part) => total * 60 + part, 0);
        }
    }

    const init = () => document.querySelectorAll('[data-region="videoerrorhunt"]').forEach((root) => new App(root).init());
    return {init: init};
});
