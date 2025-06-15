import React from 'react';
import styles from './Card.module.css';

function Card({ icon, title, description, link, className = '' }) {
  const cardClasses = `${styles.card} ${className}`.trim();

  const content = (
    <>
      {icon && <span className={styles['card-icon']}>{icon}</span>}
      <h3 className={styles['card-title']}>{title}</h3>
      <p className={styles['card-description']}>{description}</p>
    </>
  );

  if (link) {
    return (
      <a href={link} className={`${cardClasses} ${styles['card-link']}`}>
        {content}
      </a>
    );
  }

  return <div className={cardClasses}>{content}</div>;
}

export default Card;
