<section id="loginScreen" class="screen active">
    <h1 class="login-title">ログイン</h1>

    <div class="login-form">
        <label>
            <span>店舗選択</span>
            <select id="storeId" name="storeId" required>
                <option value="" disabled selected hidden>店舗を選択してください</option>
                <option value="MH">緑橋本店</option>
                <option value="MN">森ノ宮店</option>
                <option value="TZ">玉造店</option>
                <option value="TH">鶴橋店</option>
                <option value="IM">今里店</option>
                <option value="FB">深江橋店</option>
                <option value="TY">谷町四丁目店</option>
                <option value="HM">本町店</option>
                <option value="KB">京橋店</option>
                <option value="NB">なんば店</option>
            </select>
        </label>

        <label>
            <span>パスワード</span>
            <input id="loginPassword" type="password">
        </label>

        <button id="loginButton" class="white-button" type="button">ログイン</button>
        <p id="loginError" class="error-text"></p>
    </div>
</section>