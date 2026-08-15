FROM mathworks/matlab:latest

# نصب کتابخانه‌های مورد نیاز
RUN apt-get update && apt-get install -y \
    curl \
    && rm -rf /var/lib/apt/lists/*

# کپی فایل‌ها
COPY . /matlab/
RUN chmod +x /matlab/*.m

# تنظیمات
ENV MATLAB_PATH=/usr/local/MATLAB/R2023a/bin
ENV PATH=$PATH:$MATLAB_PATH

WORKDIR /matlab

# اجرای سرویس
CMD matlab -nodisplay -nosplash -nodesktop -r "try, run('/matlab/analyze_network.m'), catch, end, exit"
