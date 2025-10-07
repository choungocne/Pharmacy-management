from flask import Flask, render_template, jsonify
import json
from datetime import datetime, timedelta

app = Flask(__name__)

# Dữ liệu mẫu
def get_sample_data():
    return {
        "featured_categories": [
            {"name": "Thần kinh não", "count": "55 sản phẩm", "icon": "brain"},
            {"name": "Vitamin & Khoáng chất", "count": "110 sản phẩm", "icon": "capsules"},
            {"name": "Sức khoẻ tim mạch", "count": "23 sản phẩm", "icon": "heartbeat"},
            {"name": "Tăng sức đề kháng", "count": "40 sản phẩm", "icon": "shield-virus"},
            {"name": "Hỗ trợ tiêu hóa", "count": "65 sản phẩm", "icon": "stomach"},
            {"name": "Sinh lý - Nội tiết tố", "count": "39 sản phẩm", "icon": "heart"}
        ],
        "best_selling_products": [
            {
                "id": 1,
                "name": "Viên uống Medsulin Plus hỗ trợ cải thiện chỉ số đường huyết",
                "category": "Hỗ trợ đường huyết",
                "current_price": "768.000đ",
                "original_price": "960.000đ",
                "discount": "20%",
                "package": "Hộp 60 Viên"
            },
            {
                "id": 2,
                "name": "Siro Phế Lạc T2Om hỗ trợ bổ phế, giảm ho",
                "category": "Hỗ trợ hô hấp",
                "current_price": "60.000đ",
                "original_price": "75.000đ",
                "discount": "20%",
                "package": "Hộp"
            },
            {
                "id": 3,
                "name": "Viên nén Multi Vitas Lao Well bổ sung vitamin và khoáng chất",
                "category": "Vitamin & Khoáng chất",
                "current_price": "272.000đ",
                "original_price": "346.000đ",
                "discount": "20%",
                "package": "Hộp 60 Viên"
            }
        ],
        "deal_products": [
            {
                "id": 1,
                "name": "Bọt vệ sinh nam Sunnejy Men's Sanitary Foam làm sạch, khử...",
                "category": "Chăm sóc nam giới",
                "current_price": "101.400đ",
                "original_price": "190.000đ",
                "discount": "47%"
            },
            {
                "id": 2,
                "name": "Sữa dưỡng trắng da Transino Whitening Clear Milk EX ngăn...",
                "category": "Dưỡng da",
                "current_price": "809.600đ",
                "original_price": "920.000đ",
                "discount": "12%"
            },
            {
                "id": 3,
                "name": "Cốm Probiotics Lactomin Plus Novarex bổ sung vi khuẩn có...",
                "category": "Hỗ trợ tiêu hóa",
                "current_price": "165.300đ",
                "original_price": "174.000đ",
                "discount": "5%"
            }
        ],
        "seasonal_products": [
            {
                "id": 1,
                "name": "Nhiệt kế điện tử Omron MC-246 đo nhiệt độ cho trẻ khi sốt",
                "category": "Nhiệt kế",
                "current_price": "125.000đ",
                "original_price": "",
                "discount": ""
            },
            {
                "id": 2,
                "name": "Nhiệt kế hồng ngoại Microthe FRIMET đo không chạm, nhanh",
                "category": "Nhiệt kế",
                "current_price": "990.000đ",
                "original_price": "",
                "discount": ""
            },
            {
                "id": 3,
                "name": "Nhiệt kế hồng ngoại Vowel VT-1C đo nhiệt độ cơ thể",
                "category": "Nhiệt kế",
                "current_price": "472.000đ",
                "original_price": "590.000đ",
                "discount": "20%"
            }
        ],
        "top_searches": [
            "Omega 3", "Canxi", "Thuốc nhỏ mắt", "Sữa rửa mặt",
            "Dung dịch vệ sinh", "Men vi sinh", "Kẽm", "Kẽm chống nắng"
        ],
        "disease_categories": [
            {
                "name": "Bệnh nam giới",
                "diseases": ["Loãng xương ở nam", "Di tinh, mộng tinh", "Hẹp bao quy đầu", "Yếu sinh lý"]
            },
            {
                "name": "Bệnh nữ giới",
                "diseases": ["Hội chứng tiền kinh nguyệt", "Hội chứng tiền mãn kinh", "Chậm kinh", "Mất kinh"]
            },
            {
                "name": "Bệnh người già",
                "diseases": ["Alzheimer", "Parkinson", "Parkinson thứ phát", "Đục thủy tinh thể ở người già"]
            },
            {
                "name": "Bệnh trẻ em",
                "diseases": ["Bại não trẻ em", "Tự kỷ", "Uốn ván", "Tắc ruột sơ sinh"]
            }
        ]
    }

@app.route('/')
def index():
    data = get_sample_data()
    return render_template('index.html', **data)
@app.route('/about')
def about():
    return render_template('about.html')


@app.route('/api/deal-time')
def get_deal_time():
    # Tính thời gian còn lại đến 10:00 PM
    now = datetime.now()
    deal_end = datetime(now.year, now.month, now.day, 22, 0, 0)  # 10:00 PM
    if now > deal_end:
        deal_end += timedelta(days=1)

    time_remaining = deal_end - now
    hours = time_remaining.seconds // 3600
    minutes = (time_remaining.seconds % 3600) // 60
    seconds = time_remaining.seconds % 60

    return jsonify({
        'hours': hours,
        'minutes': minutes,
        'seconds': seconds
    })

@app.route('/api/add-to-cart/<int:product_id>', methods=['POST'])
def add_to_cart(product_id):
    return jsonify({
        'success': True,
        'message': 'Đã thêm sản phẩm vào giỏ hàng!',
        'cart_count': 1
    })

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
