# Naran Persistent Login

개발시 유용한 워드프레스 항상 로그인 플러그인.


## wp-config.php 상수

다음 상수 목록을 `wp-config.php`에 선언할 수 있습니다.

| 상수 이름      | 필수   | 설명                                                                               |
|----------------|--------|------------------------------------------------------------------------------------|
| NPL_ENABLED    | 예     | 참이어야만 항상 로그인 가능.                                                       |
| NPL_USER       | 예     | 항상 유지될 유저 로그인(user_login).                                           |
| NPL_ADDR       | 아니오 | 'REMOTE_ADDR' 서버 변수를 체크하여 이 상수와 일치해야만 동작. 기본은 '127.0.0.1'   |
| NPL_REDIRECT   | 아니오 | 강제 로그인 처리시 리다이렉트 처리할 주소. 기본 '/wp-admin/'                       |
