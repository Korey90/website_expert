/* global __ENV */

import http from "k6/http";
import { check, sleep } from "k6";
import { Counter } from "k6/metrics";

const targetUrl = __ENV.TARGET_URL || "https://tellsell.pl/";
const peakVus = Number(__ENV.PEAK_VUS || 120);
const sleepSeconds = Number(__ENV.SLEEP_SECONDS || 1);
const statusCodes = new Counter("status_codes");

export const options = {
  stages: [
    { duration: "1m", target: 5 },
    { duration: "3m", target: peakVus },
    { duration: "1m", target: 0 },
  ],
  thresholds: {
    http_req_failed: ["rate<0.01"],
    http_req_duration: ["p(95)<2000"],
  },
};

export default function () {
  const response = http.get(targetUrl, {
    tags: { name: "homepage" },
  });

  statusCodes.add(1, {
    status: String(response.status),
  });

  check(response, {
    "status is 200": (result) => result.status === 200,
  });

  sleep(sleepSeconds);
}
