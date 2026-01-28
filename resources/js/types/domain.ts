export interface DomainCheck {
    id: number;
    result: string;
    result_label: string;
    result_color: string;
    is_successful: boolean;
    response_code: number | null;
    response_time_ms: number | null;
    error_message: string | null;
    checked_at: string;
    checked_at_human: string;
}

export interface Domain {
    id: number;
    hostname: string;
    method: 'GET' | 'HEAD';
    interval: number;
    timeout: number;
    body: string | null;
    is_active: boolean;
    last_checked_at: string | null;
    checks_count?: number;
    latest_check?: DomainCheck;
    checks?: DomainCheck[];
    // Stats
    is_down: boolean;
    uptime_24h: number | null;
    uptime_30d: number | null;
    avg_response_24h: number | null;
}