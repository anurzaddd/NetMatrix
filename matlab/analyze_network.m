% NetMatrix - Network Analysis with MATLAB
% Author: Amir Hossein Nourzadeh

function result = analyze_network(data_file)
    % خواندن داده‌ها
    if nargin < 1
        data_file = '/data/network_data.csv';
    end

    try
        % خواندن فایل CSV
        data = readtable(data_file);
        
        % تحلیل آماری
        traffic_mean = mean(data.traffic);
        traffic_std = std(data.traffic);
        traffic_max = max(data.traffic);
        
        % پیش‌بینی با رگرسیون خطی
        x = (1:length(data.traffic))';
        y = data.traffic;
        p = polyfit(x, y, 1);
        predicted = polyval(p, length(x) + 1:length(x) + 24);
        
        % تشخیص ناهنجاری
        anomaly_threshold = traffic_mean + 2 * traffic_std;
        anomalies = data.traffic > anomaly_threshold;
        anomaly_count = sum(anomalies);
        
        % سطح خطر
        if anomaly_count > 5
            risk_level = 'high';
            risk_desc = 'تعداد ناهنجاری‌ها بالا است';
        elseif anomaly_count > 2
            risk_level = 'medium';
            risk_desc = 'تعداد ناهنجاری‌ها متوسط است';
        else
            risk_level = 'low';
            risk_desc = 'همه‌چیز عادی است';
        end
        
        % نتیجه
        result = struct(...
            'traffic_mean', traffic_mean, ...
            'traffic_std', traffic_std, ...
            'traffic_max', traffic_max, ...
            'traffic_prediction', mean(predicted), ...
            'anomaly_count', anomaly_count, ...
            'risk_level', risk_level, ...
            'risk_description', risk_desc, ...
            'status', 'success' ...
        );
        
    catch ME
        result = struct(...
            'status', 'error', ...
            'message', ME.message ...
        );
    end
    
    % ذخیره نتیجه به صورت JSON
    result_json = jsonencode(result);
    fid = fopen('/data/analysis_result.json', 'w');
    fprintf(fid, '%s', result_json);
    fclose(fid);
    
    % نمایش نتیجه
    disp(result_json);
end
