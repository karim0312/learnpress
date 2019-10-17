import {Component} from '@wordpress/element';
import {withSelect, withDispatch, select} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import Timer from '../timer';
import {__, sprintf} from '@wordpress/i18n';
const $ = jQuery;
const {debounce} = lodash;

class Status extends Component {
    constructor() {
        super(...arguments);

        this.state = {
            submitting: false
        }

        LP.Hook.addAction('quiz-submitted', (results) => {
            console.log(results)
        });
    }

    componentDidMount() {
        const $pc = $('#popup-content');
        const $sc = $pc.find('.content-item-scrollable:eq(1)');
        const $ciw = $pc.find('.content-item-wrap');
        const $qs = $pc.find('.quiz-status');
        const pcTop = $qs.offset().top - 92;

        let isFixed = false;
        let marginLeft = '-' + $ciw.css('margin-left');

        $(window).on('resize.refresh-quiz-stauts-bar', debounce(function () {
            marginLeft = '-' + $ciw.css('margin-left');

            $qs.css({
                'margin-left': marginLeft,
                'margin-right': marginLeft
            });
        }, 100)).trigger('resize.refresh-quiz-stauts-bar');

        /**
         * Check when status bar is stopped in the top
         * to add new class into html
         */
        $sc.scroll(() => {

            if ($sc.scrollTop() >= pcTop) {
                if (isFixed) {
                    return;
                }
                isFixed = true;
            } else {
                if (!isFixed) {
                    return;
                }
                isFixed = false;
            }

            if (isFixed) {
                $pc.addClass('fixed-quiz-status');
            } else {
                $pc.removeClass('fixed-quiz-status');
            }
        })
    };

    /**
     * Submit question to record results.
     */
    submit = () => {
        const {
            submitQuiz
        } = this.props;

        submitQuiz();
    };

    getMark = () => {
        const answered = select('learnpress/quiz').getData('answered');
        console.log('getMark')
        return Object.values(answered).reduce((m, r) => {
            return m + r.mark;
        }, 0);
    };

    render() {
        const {
            currentPage,
            questionsPerPage,
            questionsCount,
            submitting,
            totalTime,
            duration,
            userMark
        } = this.props;
        // const {
        //     submitting
        // } = this.state;
        const classNames = ['quiz-status'];

        let start = (currentPage - 1) * questionsPerPage + 1;
        let end = start + questionsPerPage - 1;

        end = Math.min(end, questionsCount);

        if (submitting) {
            classNames.push('submitting');
        }

        return <div className={ classNames.join(' ') }>
            <div>
                <div className="questions-index">
                    {
                        end < questionsCount && (
                            questionsPerPage > 1
                                ? sprintf(__('Question %d to %d of %d', 'learnpress'), start, end, questionsCount)
                                : sprintf(__('Question %d of %d', 'learnpress'), start, questionsCount)
                        )
                    }

                    {
                        end === questionsCount && sprintf(__('Question %d to %d', 'learnpress'), start, end)
                    }
                </div>

                Earned Point: {userMark}

                <div className="submit-quiz">
                    <button className="lp-button" id="button-submit-quiz"
                            onClick={ this.submit }>{ !submitting ? __('Submit', 'learnpress') : __('Submitting...', 'learnpress') }</button>
                </div>

                { totalTime && duration && <Timer  /> }

            </div>
        </div>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData,
            getUserMark
        } = select('learnpress/quiz');

        return {
            currentPage: getData('currentPage'),
            numPages: getData('numPages'),
            questionsPerPage: getData('questionsPerPage'),
            questionsCount: getData('questionIds').length,
            submitting: getData('submitting'),
            totalTime: getData('totalTime'),
            duration: getData('duration'),
            userMark: getUserMark()
        }
    }),
    withDispatch((dispatch) => {
        const {
            //setQuizData,
            submitQuiz,
            //startQuiz
        } = dispatch('learnpress/quiz');

        return {
            //setQuizData,
            submitQuiz
            //startQuiz
        }
    })
])(Status);