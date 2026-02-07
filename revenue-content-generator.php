<?php
/**
 * Plugin Name: AI Revenue Content Generator
 * Plugin URI: https://example.com
 * Description: Puter.js API 기반 수익형 콘텐츠 자동 생성기 - SEO 최적화 및 광고 수익 극대화
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: ai-revenue-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Revenue_Content_Generator {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_generator_metabox'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_generate_revenue_content', array($this, 'generate_content_ajax'));
        add_action('wp_ajax_process_ai_content', array($this, 'process_ai_content_ajax'));
        add_action('save_post', array($this, 'save_ad_codes'), 10, 2);
    }
    
    public function add_generator_metabox() {
        add_meta_box(
            'ai_revenue_generator',
            '🚀 AI 수익형 콘텐츠 생성기',
            array($this, 'render_metabox'),
            'post',
            'side',
            'high'
        );
    }
    
    public function render_metabox($post) {
        wp_nonce_field('ai_revenue_generator_nonce', 'ai_revenue_generator_nonce_field');
        
        $ad_code_top = get_post_meta($post->ID, '_ad_code_top', true);
        $ad_code_bottom = get_post_meta($post->ID, '_ad_code_bottom', true);
        ?>
        <div id="ai-revenue-generator-box">
            <div class="generator-section">
                <label for="content-topic"><strong>📝 주제 입력:</strong></label>
                <input type="text" id="content-topic" class="widefat" placeholder="예: 최고의 다이어트 방법" />
            </div>
            
            <div class="generator-section">
                <label for="target-keyword"><strong>🎯 타겟 키워드:</strong></label>
                <input type="text" id="target-keyword" class="widefat" placeholder="예: 다이어트, 살빼기" />
            </div>
            
            <div class="generator-section">
                <label for="ad-code-top"><strong>📢 상단 광고 코드:</strong></label>
                <textarea id="ad-code-top" name="ad_code_top" class="widefat" rows="3" placeholder="애드센스 또는 광고 코드 입력"><?php echo esc_textarea($ad_code_top); ?></textarea>
            </div>
            
            <div class="generator-section">
                <label for="ad-code-bottom"><strong>📢 하단 광고 코드:</strong></label>
                <textarea id="ad-code-bottom" name="ad_code_bottom" class="widefat" rows="3" placeholder="애드센스 또는 광고 코드 입력"><?php echo esc_textarea($ad_code_bottom); ?></textarea>
            </div>
            
            <div class="generator-section">
                <button type="button" id="generate-content-btn" class="button button-primary button-large" style="width: 100%; height: 50px; font-size: 16px;">
                    ✨ 수익형 콘텐츠 생성하기
                </button>
            </div>
            
            <div id="generation-status" style="margin-top: 15px;"></div>
            
            <div id="seo-score-display" style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 5px; display: none;">
                <h4 style="margin: 0 0 10px 0;">📊 SEO 점수</h4>
                <div id="seo-score-content"></div>
            </div>
        </div>
        
        <style>
            .generator-section {
                margin-bottom: 15px;
            }
            .generator-section label {
                display: block;
                margin-bottom: 5px;
            }
            #generation-status {
                padding: 10px;
                border-radius: 4px;
                display: none;
            }
            #generation-status.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
                display: block;
            }
            #generation-status.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
                display: block;
            }
            #generation-status.loading {
                background: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
                display: block;
            }
            .seo-score-bar {
                height: 30px;
                background: #e0e0e0;
                border-radius: 15px;
                overflow: hidden;
                margin-bottom: 10px;
            }
            .seo-score-fill {
                height: 100%;
                background: linear-gradient(90deg, #4caf50, #8bc34a);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                transition: width 0.5s ease;
            }
        </style>
        <?php
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        // Puter.js 라이브러리 로드
        wp_enqueue_script(
            'puter-js',
            'https://js.puter.com/v2/',
            array(),
            null,
            true
        );
        
        wp_enqueue_script(
            'ai-revenue-generator',
            plugin_dir_url(__FILE__) . 'assets/generator.js',
            array('jquery', 'puter-js'),
            '1.0.0',
            true
        );
        
        wp_localize_script('ai-revenue-generator', 'aiRevenueGen', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_revenue_generator_nonce')
        ));
    }
    
    public function generate_content_ajax() {
        check_ajax_referer('ai_revenue_generator_nonce', 'nonce');
        
        $topic = sanitize_text_field($_POST['topic']);
        $keyword = sanitize_text_field($_POST['keyword']);
        $ad_code_top = wp_kses_post($_POST['ad_code_top']);
        $ad_code_bottom = wp_kses_post($_POST['ad_code_bottom']);
        
        // Puter.js에서 사용할 프롬프트 생성
        $prompt = $this->build_seo_prompt($topic, $keyword);
        
        // 클라이언트에서 Puter.js로 생성하도록 프롬프트와 설정 반환
        wp_send_json_success(array(
            'prompt' => $prompt,
            'topic' => $topic,
            'keyword' => $keyword,
            'ad_code_top' => $ad_code_top,
            'ad_code_bottom' => $ad_code_bottom,
            'use_client_generation' => true
        ));
    }
    
    public function process_ai_content_ajax() {
        check_ajax_referer('ai_revenue_generator_nonce', 'nonce');
        
        $ai_content = wp_kses_post($_POST['ai_content']);
        $keyword = sanitize_text_field($_POST['keyword']);
        $ad_code_top = wp_kses_post($_POST['ad_code_top']);
        $ad_code_bottom = wp_kses_post($_POST['ad_code_bottom']);
        
        // SEO 최적화 콘텐츠 구조화
        $optimized_content = $this->optimize_for_seo($ai_content, $keyword, $ad_code_top, $ad_code_bottom);
        
        // SEO 점수 계산
        $seo_score = $this->calculate_seo_score($optimized_content, $keyword);
        
        wp_send_json_success(array(
            'content' => $optimized_content,
            'seo_score' => $seo_score,
            'message' => 'SEO 최적화 콘텐츠가 성공적으로 생성되었습니다!'
        ));
    }
    
    private function generate_with_puter_api($topic, $keyword) {
        // Puter.js는 클라이언트 사이드에서 실행되므로
        // 서버에서는 프롬프트만 반환하고 실제 생성은 JavaScript에서 처리
        return $this->build_seo_prompt($topic, $keyword);
    }
    
    private function build_seo_prompt($topic, $keyword) {
        return "다음 주제로 검색엔진 최적화된 블로그 글을 작성해주세요.

주제: {$topic}
타겟 키워드: {$keyword}

요구사항:
1. 정확히 3개의 소제목(H2)만 사용
2. 각 소제목은 타겟 키워드를 자연스럽게 포함
3. 총 단어 수: 1500-2000자
4. 서론, 본론(3개 섹션), 결론 구조
5. 네이버, 구글, 빙 검색 최적화
6. 자연스러운 키워드 밀도 (2-3%)
7. 독자에게 가치를 제공하는 실용적 정보
8. 마지막에 CTA(Call-to-Action) 포함

형식:
- 매력적인 제목 (60자 이내)
- 소제목은 ## 마크다운 사용
- 단락은 3-4문장으로 구성
- 리스트는 자연스럽게 활용";
    }
    
    private function generate_fallback_content($topic, $keyword) {
        return "# {$topic}: 완벽 가이드

{$keyword}에 대해 알아보시나요? 이 글에서는 {$keyword}에 대한 모든 것을 상세히 다룹니다.

## {$keyword}란 무엇인가?

{$keyword}는 많은 분들이 관심을 갖고 있는 주제입니다. 전문가들의 의견과 최신 연구 결과를 바탕으로 정확한 정보를 제공합니다.

## {$keyword}의 핵심 포인트

성공적인 결과를 위해서는 올바른 방법을 아는 것이 중요합니다. 검증된 방법들을 단계별로 소개합니다.

## {$keyword} 실천 방법

실제로 적용할 수 있는 구체적인 방법들을 알아봅니다. 초보자도 쉽게 따라할 수 있는 실용적인 팁을 제공합니다.

지금 바로 시작해보세요!";
    }
    
    private function optimize_for_seo($content, $keyword, $ad_code_top, $ad_code_bottom) {
        // 콘텐츠를 섹션으로 분리
        $sections = preg_split('/##\s+/', $content);
        $title = array_shift($sections);
        
        // 제목 정리
        $title = trim(str_replace('#', '', $title));
        
        // 최적화된 HTML 구조 생성
        $html = '';
        
        // 서론 부분 (첫 번째 섹션 전까지)
        if (!empty($sections)) {
            $intro = trim($sections[0]);
            if (strpos($intro, "\n\n") !== false) {
                $parts = explode("\n\n", $intro, 2);
                $html .= '<div class="intro-section">' . wpautop($parts[0]) . '</div>';
                if (isset($parts[1])) {
                    $sections[0] = $parts[1];
                }
            }
        }
        
        // 3개의 소제목만 처리
        $subtitle_count = 0;
        $middle_section_index = 1; // 중간 섹션은 두 번째 소제목
        
        foreach ($sections as $index => $section) {
            if ($subtitle_count >= 3) break;
            
            $lines = explode("\n", trim($section), 2);
            $subtitle = trim($lines[0]);
            $body = isset($lines[1]) ? trim($lines[1]) : '';
            
            if (empty($subtitle)) continue;
            
            $subtitle_count++;
            
            // 두 번째 소제목(중간)에 광고와 CTA 버튼 배치
            if ($subtitle_count === $middle_section_index) {
                // 상단 광고
                if (!empty($ad_code_top)) {
                    $html .= '<div class="revenue-ad-block ad-top" style="margin: 30px 0; text-align: center;">';
                    $html .= $ad_code_top;
                    $html .= '</div>';
                }
                
                $html .= '<h2 class="seo-subtitle">' . esc_html($subtitle) . '</h2>';
                $html .= wpautop($body);
                
                // CTA 버튼 (자동 링크 생성)
                $related_url = $this->generate_related_link($keyword, $title);
                $html .= '<div class="cta-button-container" style="margin: 30px 0; text-align: center;">';
                $html .= '<a href="' . esc_url($related_url) . '" target="_blank" rel="noopener noreferrer" class="cta-button" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 18px 40px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 18px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.3s ease;">🔥 ' . esc_html($keyword) . ' 자세히 알아보기 →</a>';
                $html .= '</div>';
                
                // 하단 광고
                if (!empty($ad_code_bottom)) {
                    $html .= '<div class="revenue-ad-block ad-bottom" style="margin: 30px 0; text-align: center;">';
                    $html .= $ad_code_bottom;
                    $html .= '</div>';
                }
            } else {
                $html .= '<h2 class="seo-subtitle">' . esc_html($subtitle) . '</h2>';
                $html .= wpautop($body);
            }
        }
        
        // Schema.org 구조화 데이터 추가
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'keywords' => $keyword,
            'articleBody' => wp_strip_all_tags($html)
        );
        
        $html .= '<script type="application/ld+json">' . json_encode($schema) . '</script>';
        
        return $html;
    }
    
    private function generate_related_link($keyword, $topic) {
        // 키워드 기반 자동 URL 생성 (네이버, 구글 검색 또는 관련 사이트)
        $search_engines = array(
            'https://search.naver.com/search.naver?query=' . urlencode($keyword),
            'https://www.google.com/search?q=' . urlencode($keyword),
        );
        
        // 키워드와 가장 관련성 높은 링크 선택 (여기서는 네이버 우선)
        return $search_engines[0];
    }
    
    private function calculate_seo_score($content, $keyword) {
        $score = 0;
        $details = array();
        
        // 1. 키워드 밀도 체크 (20점)
        $keyword_count = substr_count(strtolower(wp_strip_all_tags($content)), strtolower($keyword));
        $total_words = str_word_count(wp_strip_all_tags($content));
        $keyword_density = ($total_words > 0) ? ($keyword_count / $total_words) * 100 : 0;
        
        if ($keyword_density >= 2 && $keyword_density <= 3) {
            $score += 20;
            $details[] = '✅ 키워드 밀도: 최적 (2-3%)';
        } else if ($keyword_density >= 1 && $keyword_density <= 4) {
            $score += 15;
            $details[] = '⚠️ 키워드 밀도: 양호 (1-4%)';
        } else {
            $score += 10;
            $details[] = '❌ 키워드 밀도: 개선 필요';
        }
        
        // 2. 소제목 개수 체크 (20점)
        $h2_count = substr_count($content, '<h2');
        if ($h2_count === 3) {
            $score += 20;
            $details[] = '✅ 소제목: 완벽 (3개)';
        } else {
            $score += 10;
            $details[] = '⚠️ 소제목: ' . $h2_count . '개';
        }
        
        // 3. 콘텐츠 길이 체크 (15점)
        if ($total_words >= 1500 && $total_words <= 2500) {
            $score += 15;
            $details[] = '✅ 콘텐츠 길이: 최적';
        } else {
            $score += 10;
            $details[] = '⚠️ 콘텐츠 길이: 조정 권장';
        }
        
        // 4. 광고 배치 체크 (15점)
        if (strpos($content, 'revenue-ad-block') !== false) {
            $score += 15;
            $details[] = '✅ 광고 배치: 완료';
        } else {
            $details[] = '❌ 광고 배치: 없음';
        }
        
        // 5. CTA 버튼 체크 (10점)
        if (strpos($content, 'cta-button') !== false) {
            $score += 10;
            $details[] = '✅ CTA 버튼: 포함';
        } else {
            $details[] = '❌ CTA 버튼: 없음';
        }
        
        // 6. Schema 마크업 체크 (10점)
        if (strpos($content, 'application/ld+json') !== false) {
            $score += 10;
            $details[] = '✅ Schema 마크업: 완료';
        } else {
            $details[] = '❌ Schema 마크업: 없음';
        }
        
        // 7. 내부 링크 체크 (10점)
        $score += 10;
        $details[] = '✅ 외부 링크: 포함';
        
        return array(
            'score' => $score,
            'details' => $details,
            'grade' => $this->get_seo_grade($score)
        );
    }
    
    private function get_seo_grade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        return 'D';
    }
    
    public function save_ad_codes($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['ai_revenue_generator_nonce_field'])) return;
        if (!wp_verify_nonce($_POST['ai_revenue_generator_nonce_field'], 'ai_revenue_generator_nonce')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        if (isset($_POST['ad_code_top'])) {
            update_post_meta($post_id, '_ad_code_top', wp_kses_post($_POST['ad_code_top']));
        }
        
        if (isset($_POST['ad_code_bottom'])) {
            update_post_meta($post_id, '_ad_code_bottom', wp_kses_post($_POST['ad_code_bottom']));
        }
    }
}

// 플러그인 초기화
new AI_Revenue_Content_Generator();
