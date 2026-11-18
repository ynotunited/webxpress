/**
 * The contact page's guided project brief.
 *
 * This lives in its own entry point rather than in an inline <script> in the
 * Blade template for two reasons. It was 14 KB of the HTML on every visit to
 * /contact, and because it was inlined it could never be cached separately
 * from the document: revisiting the page downloaded all of it again. As a
 * module it is fetched in parallel, cached across visits, and absent from
 * every other page.
 *
 * Registered on alpine:init rather than at module scope so Alpine is already
 * listening. Alpine.start() in app.js runs on DOMContentLoaded; a deferred
 * module also executes after document parsing, and alpine:init fires during
 * Alpine.start(), so the listener is registered in time.
 */
document.addEventListener('alpine:init', () => {
    window.startProject = function startProject() {
        return {
            /* ── State ─────────────────────────────────────────────────── */
            messages:          [],
            answers:           {},
            currentInput:      '',
            currentOptions:    [],
            currentPlaceholder:'',
            currentQuestion:   '',
            inputType:         'options',
            showInput:         false,
            showReview:        false,
            isTyping:          true,
            isSubmitting:      false,
            isDone:            false,
            inputError:        '',
            currentQuestionIndex: -1,
            submitError:       '',

            /* Every pending timeout is tracked so destroy() can cancel
               them all; an orphaned timer would mutate this component
               after teardown and keep its state graph reachable. */
            _timers: [],

            /* ── Questions ─────────────────────────────────────────────── */
            questions: [
                {
                    id: 'project_type',
                    text: 'Let\'s start - what type of project is this?',
                    type: 'options',
                    options: [
                        { value: 'website',     label: 'Website' },
                        { value: 'ecommerce',   label: 'E-commerce' },
                        { value: 'webapp',      label: 'Web App' },
                        { value: 'saas',        label: 'SaaS Platform' },
                        { value: 'mobile',      label: 'Mobile App' },
                        { value: 'branding',    label: 'Branding / Design' },
                        { value: 'other',       label: 'Something else' },
                    ],
                },
                {
                    id: 'name',
                    text: 'What\'s your name?',
                    type: 'text',
                    placeholder: 'Your name',
                },
                {
                    id: 'email',
                    text: (_a) => `Good to meet you, ${_a.name}! What's the best email to reach you?`,
                    type: 'email',
                    placeholder: 'you@company.com',
                },
                {
                    id: 'message',
                    text: 'Tell us about your vision - what are you building and what problem does it solve?',
                    type: 'textarea',
                    placeholder: 'Describe your project, goals, and who it\'s for.',
                },
                {
                    id: 'budget',
                    text: 'What budget range are you working with?',
                    type: 'options',
                    options: [
                        { value: 'Under ?500K',     label: 'Under ?500K' },
                        { value: '?500K - ?2M',     label: '?500K - ?2M' },
                        { value: '?2M - ?5M',       label: '?2M - ?5M' },
                        { value: '?2M - ?10M',      label: '?2M - ?10M' },
                        { value: '?10M+',            label: '?10M+' },
                        { value: 'Not sure yet',     label: 'Not sure yet' },
                    ],
                },
                {
                    id: 'timeline',
                    text: 'When do you need this live?',
                    type: 'options',
                    options: [
                        { value: 'ASAP',            label: 'ASAP' },
                        { value: '1 - 2 months',    label: '1 - 2 months' },
                        { value: '3 - 6 months',    label: '3 - 6 months' },
                        { value: '6+ months',       label: '6+ months' },
                        { value: 'Flexible',        label: 'Flexible' },
                    ],
                },
            ],

            /* ── Computed ──────────────────────────────────────────────── */
            get totalAnswered() {
                return this.questions.filter(q => this.answers[q.id]).length;
            },

            /* ── Init ──────────────────────────────────────────────────── */
            /** Schedule a callback and remember it so destroy() can cancel it. */
            later(fn, delay) {
                const id = setTimeout(() => {
                    this._timers = this._timers.filter((t) => t !== id);
                    fn();
                }, delay);
                this._timers.push(id);
                return id;
            },
            /** Cancel every pending timer; used by goBack() and destroy(). */
            clearTimers() {
                this._timers.forEach(clearTimeout);
                this._timers = [];
            },

            /**
             * Move keyboard focus to whatever the current step offers.
             *
             * Every answer() hides the controls the user just activated
             * and askQuestion() renders a different question 750ms later.
             * Because the focused element was removed from the DOM, the
             * browser reset focus to <body>, so a keyboard or screen reader
             * user had to rediscover the question from the top of the
             * document on every single step. Only the text questions were
             * ever focused, and only because of a per-input $watch hack.
             *
             * This is called on every step transition instead. Order
             * matters: the first option, then the field, then the review
             * button -- whichever is actually rendered for this step.
             */
            focusCurrentControl() {
                this.$nextTick(() => {
                    const target =
                        this.$refs.options?.querySelector('button') ||
                        this.$refs.textInput ||
                        this.$refs.textareaInput ||
                        this.$refs.reviewHeading ||
                        null;

                    if (target) {
                        target.focus({ preventScroll: true });
                    }

                    /* keepReducedFocus() is about the browser scrolling the
                       page; do that here, where we know the new step just
                       appeared, instead of hijacking it a second time. */
                    if (this.$refs.inputArea?.offsetParent) {
                        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        this.$refs.inputArea.scrollIntoView({
                            behavior: reduced ? 'auto' : 'smooth',
                            block: 'nearest',
                        });
                    }
                });
            },
            destroy() {
                this.clearTimers();
                this._abort?.abort();
                this._abort = null;
            },
            /**
             * Runs once when Alpine initialises this component.
             *
             * Every message in the chat is pushed from this method's timers, so
             * if it does not run the conversation stays empty with no error to
             * explain it. Two things have to hold for that. The hook must be
             * named `init`: Alpine v3 calls exactly two lifecycle hooks,
             * `init` and `destroy`, and silently ignores any other name. And
             * the component must already be registered when Alpine starts,
             * which is why the registration above listens for alpine:init and
             * why app.js waits for DOMContentLoaded before calling start().
             */
            init() {
                this.isTyping = true;
                this.later(() => {
                    this.isTyping = false;
                    this.messages.push({
                        role: 'brand',
                        text: "Hey! We're Webxpress - we build digital products that move businesses forward.",
                    });
                    this.scroll();
                    this.later(() => this.askQuestion(0), 500);
                }, 900);
            },

            /* ── Ask next question ─────────────────────────────────────── */
            askQuestion(index) {
                this.currentQuestionIndex = index;
                this.isTyping = true;
                this.showInput = false;
                this.inputError = '';
                this.scroll();

                const q = this.questions[index];
                const text = typeof q.text === 'function' ? q.text(this.answers) : q.text;

                this.later(() => {
                    this.isTyping = false;
                    this.messages.push({ role: 'brand', text: text });
                    this.inputType = q.type;
                    this.currentQuestion = text;
                    this.currentOptions = q.options || [];
                    this.currentPlaceholder = q.placeholder || '';
                    this.showInput = true;
                    this.scroll();
                    this.focusCurrentControl();
                }, 750);
            },

            /* ── Answer ────────────────────────────────────────────────── */
            answer(value, label) {
                this.answers[this.questions[this.currentQuestionIndex].id] = value;
                this.messages.push({ role: 'user', text: label });
                this.showInput = false;
                this.currentInput = '';
                this.inputError = '';

                const next = this.currentQuestionIndex + 1;
                if (next < this.questions.length) {
                    this.askQuestion(next);
                } else {
                    this.showReviewCard();
                }
            },

            submitTextInput() {
                const val = this.currentInput.trim();
                if (!val) {
                    this.inputError = 'Please enter a value before continuing.';
                    return;
                }
                if (this.inputType === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) {
                    this.inputError = 'Please enter a valid email address, for example you@company.com.';
                    return;
                }
                this.answer(val, val);
            },

            /* ── Review ────────────────────────────────────────────────── */
            showReviewCard() {
                this.isTyping = true;
                this.showInput = false;
                this.scroll();

                this.later(() => {
                    this.isTyping = false;
                    this.messages.push({
                        role: 'brand',
                        text: 'Perfect - here\'s a quick summary. Give it a look and send it over when you\'re ready.',
                    });
                    this.showReview = true;
                    this.scroll();
                    this.focusCurrentControl();
                }, 800);
            },

            /* ── Edit / go back ───────────────────────────────────────── */
            goBack() {
                // Cancel pending question timers, or an in-flight askQuestion
                // lands after the reset and overwrites it.
                this.clearTimers();
                this.showReview = false;
                this.currentQuestionIndex = 0;
                this.messages = [];
                this.answers = {};
                this.showInput = false;
                this.isTyping = false;
                this.inputError = '';
                this.scroll();
                this.askQuestion(0);
            },

            /* ── Submit ────────────────────────────────────────────────── */
            submitForm() {
                this.isSubmitting = true;
                this.submitError = '';

                const payload = {
                    name:    this.answers.name,
                    email:   this.answers.email,
                    project_type: this.answers.project_type,
                    budget:  this.answers.budget,
                    timeline:this.answers.timeline,
                    message: this.answers.message,
                };

                this._abort?.abort();
                this._abort = new AbortController();

                fetch(window.CONTACT_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type':            'application/json',
                        'X-CSRF-TOKEN':            document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With':        'XMLHttpRequest',
                        'Accept':                  'application/json',
                    },
                    body: JSON.stringify(payload),
                    signal: this._abort.signal,
                })
                .then(r => {
                    if (!r.ok) throw new Error(r.status);
                    return r.json();
                })
                .then(() => {
                    this.isDone = true;
                    this.showReview = false;
                    this.scroll();
                    this.$nextTick(() => this.$refs.successHeading?.focus());
                })
                .catch((error) => {
                    if (error.name === 'AbortError') return;
                    this.submitError = 'Something went wrong - please try again.';
                    this.isSubmitting = false;
                });
            },

            /* ── Scroll ────────────────────────────────────────────────── */
            scroll() {
                this.$nextTick(() => {
                    const el = this.$refs.chat;
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'end' });
                });
            },
        };
    };
});