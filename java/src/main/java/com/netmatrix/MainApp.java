package com.netmatrix;

import com.sun.net.httpserver.HttpServer;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpExchange;

import java.io.*;
import java.net.InetSocketAddress;
import java.net.NetworkInterface;
import java.net.SocketException;
import java.sql.*;
import java.util.*;
import java.nio.charset.StandardCharsets;

public class MainApp {
    private static Connection dbConnection;

    public static void main(String[] args) throws Exception {
        // اتصال به پایگاه داده
        connectToDatabase();

        // راه‌اندازی سرور HTTP
        HttpServer server = HttpServer.create(new InetSocketAddress(8080), 0);
        server.createContext("/api/status", new StatusHandler());
        server.createContext("/api/scan", new ScanHandler());
        server.createContext("/api/devices", new DevicesHandler());
        server.setExecutor(null);

        System.out.println("✅ Java Service running on port 8080");
        server.start();
    }

    private static void connectToDatabase() {
        try {
            String host = System.getenv().getOrDefault("DB_HOST", "mysql");
            String db = System.getenv().getOrDefault("DB_NAME", "netmatrix");
            String user = System.getenv().getOrDefault("DB_USER", "netuser");
            String pass = System.getenv().getOrDefault("DB_PASS", "netpass123");

            Class.forName("com.mysql.cj.jdbc.Driver");
            dbConnection = DriverManager.getConnection(
                "jdbc:mysql://" + host + ":3306/" + db + "?useSSL=false&serverTimezone=UTC",
                user, pass
            );
            System.out.println("✅ Connected to database");
        } catch (Exception e) {
            System.err.println("❌ Database connection failed: " + e.getMessage());
        }
    }

    // Handler برای وضعیت
    static class StatusHandler implements HttpHandler {
        @Override
        public void handle(HttpExchange exchange) throws IOException {
            String response = "{\"status\":\"online\",\"timestamp\":\"" + new Date() + "\"}";
            exchange.getResponseHeaders().set("Content-Type", "application/json");
            exchange.sendResponseHeaders(200, response.length());
            OutputStream os = exchange.getResponseBody();
            os.write(response.getBytes(StandardCharsets.UTF_8));
            os.close();
        }
    }

    // Handler برای اسکن شبکه
    static class ScanHandler implements HttpHandler {
        @Override
        public void handle(HttpExchange exchange) throws IOException {
            try {
                List<Device> devices = scanNetwork();
                String response = "{\"devices_found\": " + devices.size() + "}";
                exchange.getResponseHeaders().set("Content-Type", "application/json");
                exchange.sendResponseHeaders(200, response.length());
                OutputStream os = exchange.getResponseBody();
                os.write(response.getBytes(StandardCharsets.UTF_8));
                os.close();
            } catch (Exception e) {
                String response = "{\"error\":\"" + e.getMessage() + "\"}";
                exchange.sendResponseHeaders(500, response.length());
                OutputStream os = exchange.getResponseBody();
                os.write(response.getBytes(StandardCharsets.UTF_8));
                os.close();
            }
        }
    }

    // Handler برای دستگاه‌ها
    static class DevicesHandler implements HttpHandler {
        @Override
        public void handle(HttpExchange exchange) throws IOException {
            try {
                List<Map<String, Object>> devices = getDevicesFromDB();
                String response = new com.google.gson.Gson().toJson(devices);
                exchange.getResponseHeaders().set("Content-Type", "application/json");
                exchange.sendResponseHeaders(200, response.length());
                OutputStream os = exchange.getResponseBody();
                os.write(response.getBytes(StandardCharsets.UTF_8));
                os.close();
            } catch (Exception e) {
                String response = "{\"error\":\"" + e.getMessage() + "\"}";
                exchange.sendResponseHeaders(500, response.length());
                OutputStream os = exchange.getResponseBody();
                os.write(response.getBytes(StandardCharsets.UTF_8));
                os.close();
            }
        }
    }

    // متد اسکن شبکه
    private static List<Device> scanNetwork() throws SocketException {
        List<Device> devices = new ArrayList<>();
        Enumeration<NetworkInterface> nets = NetworkInterface.getNetworkInterfaces();
        for (NetworkInterface netIf : Collections.list(nets)) {
            Enumeration<java.net.InetAddress> addresses = netIf.getInetAddresses();
            while (addresses.hasMoreElements()) {
                java.net.InetAddress addr = addresses.nextElement();
                if (addr.isSiteLocalAddress()) {
                    Device dev = new Device();
                    dev.setIp(addr.getHostAddress());
                    dev.setName(addr.getHostName());
                    dev.setMac(netIf.getHardwareAddress() != null ?
                               bytesToHex(netIf.getHardwareAddress()) : "Unknown");
                    devices.add(dev);
                }
            }
        }
        return devices;
    }

    private static String bytesToHex(byte[] bytes) {
        StringBuilder sb = new StringBuilder();
        for (byte b : bytes) {
            sb.append(String.format("%02X:", b));
        }
        return sb.length() > 0 ? sb.substring(0, sb.length() - 1) : "";
    }

    private static List<Map<String, Object>> getDevicesFromDB() throws SQLException {
        List<Map<String, Object>> devices = new ArrayList<>();
        if (dbConnection == null) return devices;

        Statement stmt = dbConnection.createStatement();
        ResultSet rs = stmt.executeQuery("SELECT * FROM devices ORDER BY id DESC LIMIT 50");
        while (rs.next()) {
            Map<String, Object> device = new HashMap<>();
            device.put("id", rs.getInt("id"));
            device.put("name", rs.getString("name"));
            device.put("ip", rs.getString("ip"));
            device.put("mac", rs.getString("mac"));
            device.put("vendor", rs.getString("vendor"));
            device.put("status", rs.getString("status"));
            devices.add(device);
        }
        return devices;
    }

    static class Device {
        private String ip;
        private String name;
        private String mac;

        public String getIp() { return ip; }
        public void setIp(String ip) { this.ip = ip; }
        public String getName() { return name; }
        public void setName(String name) { this.name = name; }
        public String getMac() { return mac; }
        public void setMac(String mac) { this.mac = mac; }
    }
}
