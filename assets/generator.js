/**
 * AI Revenue Content Generator - Frontend Script
 * SEO 최적화 및 수익형 콘텐츠 자동 생성 (Puter.js 클라이언트 사이드)
 */

(function($) {
    'use strict';
    
    let puterAI = null;
    
    $(document).ready(function() {
        
        // Puter.js 초기화
        initializePuter();
        
        // 콘텐츠 생성 버튼 클릭 이벤트
        $('#generate-content-btn').on('click', function(e) {
            e.preventDefault();
            
            const topic = $('#content-topic').val().trim();
            const keyword = $('#target-keyword').val().trim();
            const adCodeTop = $('#ad-code-top').val().trim();
            const adCodeBottom = $('#ad-code-bottom').val().trim();
            
            // 입력 검증
            if (!topic) {
                showStatus('주제를 입력해주세요.', 'error');
                return;
            }
            
            if (!keyword) {
                showStatus('타겟 키워드를 입력해주세요.', 'error');
                return;
            }
            
            // 생성 시작
            generateContent(topic, keyword, adCodeTop, adCodeBottom);
        });
        
        async function initializePuter() {
            try {
                // Puter.js가 로드될 때까지 대기
                if (typeof puter !== 'undefined') {
                    puterAI = puter.ai;
                    console.log('✅ Puter.js 초기화 완료');
                } else {
                    console.warn('⚠️ Puter.js 로드 대기 중...');
                    setTimeout(initializePuter, 500);
                }
            } catch (error) {
                console.error('❌ Puter.js 초기화 실패:', error);
            }
        }
        
        async function generateContent(topic, keyword, adCodeTop, adCodeBottom) {
            const $btn = $('#generate-content-btn');
            const originalText = $btn.html();
            
            // 버튼 비활성화 및 로딩 상태
            $btn.prop('disabled', true).html('⏳ AI 생성 중...');
            showStatus('Puter.js AI가 SEO 최적화 콘텐츠를 생성하고 있습니다...', 'loading');
            
            try {
                // 1단계: 서버에서 프롬프트 가져오기
                const promptData = await getPromptFromServer(topic, keyword, adCodeTop, adCodeBottom);
                
                if (!promptData.success || !promptData.data.use_client_generation) {
                    throw new Error('프롬프트 생성 실패');
                }
                
                // 2단계: Puter.js로 AI 콘텐츠 생성
                $btn.html('🤖 AI 분석 중...');
                const aiContent = await generateWithPuterAI(promptData.data.prompt);
                
                // 3단계: 서버에서 SEO 최적화 및 광고 삽입
                $btn.html('📊 SEO 최적화 중...');
                const finalContent = await processAIContent(
                    aiContent,
                    promptData.data.keyword,
                    promptData.data.ad_code_top,
                    promptData.data.ad_code_bottom
                );
                
                if (finalContent.success) {
                    // 에디터에 콘텐츠 삽입
                    insertContentToEditor(finalContent.data.content);
                    
                    // SEO 점수 표시
                    displaySeoScore(finalContent.data.seo_score);
                    
                    // 성공 메시지
                    showStatus(finalContent.data.message, 'success');
                    
                    // 제목 자동 생성 제안
                    suggestTitle(topic, keyword);
                } else {
                    throw new Error(finalContent.data.message);
                }
                
            } catch (error) {
                console.error('생성 오류:', error);
                showStatus('오류: ' + error.message, 'error');
            } finally {
                // 버튼 복원
                $btn.prop('disabled', false).html(originalText);
            }
        }
        
        function getPromptFromServer(topic, keyword, adCodeTop, adCodeBottom) {
            return $.ajax({
                url: aiRevenueGen.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_revenue_content',
                    nonce: aiRevenueGen.nonce,
                    topic: topic,
                    keyword: keyword,
                    ad_code_top: adCodeTop,
                    ad_code_bottom: adCodeBottom
                }
            });
        }
        
        async function generateWithPuterAI(prompt) {
            if (!puterAI) {
                throw new Error('Puter.js AI가 초기화되지 않았습니다. 페이지를 새로고침해주세요.');
            }
            
            try {
                // Puter.js AI를 사용하여 콘텐츠 생성
                const response = await puterAI.chat(prompt, {
                    model: 'claude-3.5-sonnet', // 또는 gpt-4o
                    temperature: 0.7,
                    max_tokens: 3000
                });
                
                // 응답에서 텍스트 추출
                let content = '';
                
                if (typeof response === 'string') {
                    content = response;
                } else if (response.message) {
                    content = response.message;
                } else if (response.content) {
                    content = response.content;
                } else if (Array.isArray(response)) {
                    content = response.join('\n');
                }
                
                if (!content || content.trim().length < 100) {
                    throw new Error('AI가 충분한 콘텐츠를 생성하지 못했습니다.');
                }
                
                console.log('✅ Puter.js AI 생성 완료:', content.substring(0, 100) + '...');
                return content;
                
            } catch (error) {
                console.error('Puter.js AI 오류:', error);
                throw new Error('AI 콘텐츠 생성 실패: ' + error.message);
            }
        }
        
        function processAIContent(aiContent, keyword, adCodeTop, adCodeBottom) {
            return $.ajax({
                url: aiRevenueGen.ajax_url,
                type: 'POST',
                data: {
                    action: 'process_ai_content',
                    nonce: aiRevenueGen.nonce,
                    ai_content: aiContent,
                    keyword: keyword,
                    ad_code_top: adCodeTop,
                    ad_code_bottom: adCodeBottom
                }
            });
        }
        
        function insertContentToEditor(content) {
            // Gutenberg 에디터 확인
            if (wp.data && wp.data.select('core/editor')) {
                insertToGutenberg(content);
            } 
            // 클래식 에디터 확인
            else if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                insertToClassicEditor(content);
            }
            // 텍스트 에디터
            else {
                insertToTextEditor(content);
            }
        }
        
        function insertToGutenberg(content) {
            try {
                const { dispatch, select } = wp.data;
                const blocks = wp.blocks.rawHandler({ HTML: content });
                
                // 기존 블록 가져오기
                const currentBlocks = select('core/editor').getBlocks();
                
                // 새 블록 추가
                dispatch('core/editor').insertBlocks(blocks, currentBlocks.length);
                
                // 성공 알림
                dispatch('core/notices').createSuccessNotice(
                    '✅ SEO 최적화 콘텐츠가 성공적으로 삽입되었습니다!',
                    { type: 'snackbar', isDismissible: true }
                );
            } catch (error) {
                console.error('Gutenberg insert error:', error);
                fallbackInsert(content);
            }
        }
        
        function insertToClassicEditor(content) {
            try {
                const editor = tinymce.get('content');
                editor.setContent(editor.getContent() + content);
                editor.save();
            } catch (error) {
                console.error('Classic editor insert error:', error);
                fallbackInsert(content);
            }
        }
        
        function insertToTextEditor(content) {
            const $textarea = $('#content');
            if ($textarea.length) {
                $textarea.val($textarea.val() + '\n\n' + content);
            } else {
                fallbackInsert(content);
            }
        }
        
        function fallbackInsert(content) {
            // 모든 방법이 실패한 경우 클립보드에 복사
            copyToClipboard(content);
            showStatus('콘텐츠가 클립보드에 복사되었습니다. 에디터에 붙여넣기 해주세요.', 'success');
        }
        
        function copyToClipboard(text) {
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        }
        
        function displaySeoScore(seoData) {
            const $display = $('#seo-score-display');
            const $content = $('#seo-score-content');
            
            let html = '<div class="seo-score-bar">';
            html += '<div class="seo-score-fill" style="width: ' + seoData.score + '%">';
            html += seoData.score + '점 (' + seoData.grade + ')';
            html += '</div></div>';
            
            html += '<div class="seo-details">';
            seoData.details.forEach(function(detail) {
                html += '<p style="margin: 5px 0; font-size: 13px;">' + detail + '</p>';
            });
            html += '</div>';
            
            // RankMath 스타일 점수 메시지
            if (seoData.score >= 90) {
                html += '<p style="margin-top: 10px; padding: 10px; background: #d4edda; border-left: 4px solid #28a745; font-weight: bold;">🎉 완벽한 SEO 점수! 검색엔진 상위노출 준비 완료!</p>';
            } else if (seoData.score >= 80) {
                html += '<p style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; font-weight: bold;">👍 우수한 SEO 점수! 약간의 개선으로 완벽해질 수 있습니다.</p>';
            } else {
                html += '<p style="margin-top: 10px; padding: 10px; background: #f8d7da; border-left: 4px solid #dc3545; font-weight: bold;">⚠️ SEO 개선이 필요합니다.</p>';
            }
            
            $content.html(html);
            $display.fadeIn();
        }
        
        function suggestTitle(topic, keyword) {
            // 제목 필드가 비어있으면 자동으로 SEO 최적화 제목 제안
            const $titleField = $('#title');
            
            if ($titleField.length && !$titleField.val().trim()) {
                const suggestedTitle = keyword + ' - ' + topic + ' | 완벽 가이드 2026';
                $titleField.val(suggestedTitle).trigger('input');
                
                // Gutenberg 제목 업데이트
                if (wp.data && wp.data.select('core/editor')) {
                    wp.data.dispatch('core/editor').editPost({ title: suggestedTitle });
                }
            }
        }
        
        function showStatus(message, type) {
            const $status = $('#generation-status');
            $status.removeClass('success error loading').addClass(type);
            $status.html(message).fadeIn();
            
            // 성공/오류 메시지는 5초 후 자동 숨김
            if (type === 'success' || type === 'error') {
                setTimeout(function() {
                    $status.fadeOut();
                }, 5000);
            }
        }
        
        // 광고 코드 입력 시 실시간 검증
        $('#ad-code-top, #ad-code-bottom').on('blur', function() {
            const $this = $(this);
            const code = $this.val().trim();
            
            if (code && !isValidAdCode(code)) {
                alert('⚠️ 광고 코드 형식을 확인해주세요. HTML 형식의 광고 코드를 입력해야 합니다.');
            }
        });
        
        function isValidAdCode(code) {
            // 기본적인 HTML 태그 검증
            return code.includes('<') && code.includes('>');
        }
        
        // 키워드 입력 시 엔터키로 생성 가능
        $('#content-topic, #target-keyword').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#generate-content-btn').trigger('click');
            }
        });
        
        // 툴팁 추가
        addTooltips();
        
        function addTooltips() {
            const tooltips = {
                '#content-topic': '예: "2026년 최고의 다이어트 방법", "초보자를 위한 투자 가이드"',
                '#target-keyword': '예: "다이어트", "재테크", "부업" (쉼표로 여러 키워드 입력 가능)',
                '#ad-code-top': '버튼 위에 표시될 광고 코드 (애드센스, 쿠팡 파트너스 등)',
                '#ad-code-bottom': '버튼 아래에 표시될 광고 코드'
            };
            
            $.each(tooltips, function(selector, text) {
                $(selector).attr('title', text);
            });
        }
        
        // Puter.js 상태 모니터링
        setInterval(function() {
            if (!puterAI && typeof puter !== 'undefined') {
                initializePuter();
            }
        }, 2000);
    });
    
})(jQuery);
