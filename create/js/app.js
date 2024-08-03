const question_form = document.querySelector('.question');
const add_button = document.querySelector('#add');

let question_no = 0;

add_button.addEventListener('click', () => { // 質問追加
    // 新しいフィールドセットを作成
    const fieldset = document.createElement('fieldset');

    fieldset.innerHTML = `
        <label for="title-${question_no}">質問 : </label>
        <input type="text" name="title-${question_no}"><br>
        
        <label for="type-${question_no}">回答タイプ : </label>
        <select name="type-${question_no}" id="type-${question_no}">
            <option value="text">テキスト</option>
            <option value="select">選択肢</option>
        </select>
        
        <label for="required-${question_no}">必須 / 任意 : </label>
        <select name="required-${question_no}">
            <option value="true">必須</option>
            <option value="false">任意</option>
        </select>
        
        <div id="option-${question_no}">
        </div>
    `;

    question_form.appendChild(fieldset);

    // イベントリスナーを追加
    const typeSelect = document.querySelector(`#type-${question_no}`);
    const optionDiv = document.querySelector(`#option-${question_no}`);

    typeSelect.addEventListener('change', () => {
        console.log('Change');
        if (typeSelect.value === "select") {
            optionDiv.innerHTML = `<textarea name="option-${question_no}" cols='60' rows='10' placeholder="一行に一つ選択肢を入力してください"></textarea>`;
        } else {
            optionDiv.innerHTML = "";
        }
    });

    question_no += 1;
});
