package com.aiphra.backend;

import com.aiphra.backend.config.AppProperties;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.context.properties.EnableConfigurationProperties;

/**
 * Точка входа приложения Aiphra Backend.
 */
@SpringBootApplication
@EnableConfigurationProperties(AppProperties.class)
public class AiphraBackendApplication {
    public static void main(String[] args) {
        SpringApplication.run(AiphraBackendApplication.class, args);
    }
}
